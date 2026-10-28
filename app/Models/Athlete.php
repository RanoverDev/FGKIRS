<?php

namespace Models;

use Core\Database;
use PDO;

/**
 * Cadastro permanente do atleta no dojo. Independe de login e de evento.
 * A inscrição em campeonato (fase 4) vai copiar daqui a foto dos dados do dia.
 */
class Athlete
{
    public const GENDERS = ['M' => 'Masculino', 'F' => 'Feminino'];

    public const STATUSES = [
        'active'   => 'Ativo',
        'inactive' => 'Inativo',
        'pending'  => 'Pendente',
    ];

    public const SORTABLE = ['name', 'age', 'gender', 'belt', 'weight', 'status'];

    private const ACCENTS = [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n',
    ];

    /** Sem acento, minúsculo, espaços únicos: chave para reconhecer o mesmo atleta digitado de formas diferentes. */
    public static function normalizeName(string $name): string
    {
        $name = strtr(mb_strtolower(trim($name), 'UTF-8'), self::ACCENTS);

        return preg_replace('/\s+/', ' ', $name);
    }

    public static function isValidCpf(string $digits): bool
    {
        if (!preg_match('/^\d{11}$/', $digits) || preg_match('/^(\d)\1{10}$/', $digits)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($i = 0; $i < $t; $i++) {
                $sum += (int) $digits[$i] * (($t + 1) - $i);
            }

            if ((int) $digits[$t] !== ((10 * $sum) % 11) % 10) {
                return false;
            }
        }

        return true;
    }

    /** O SQL do convidado (migration 010) pode ainda não ter rodado: sem as colunas, a opção some e nada quebra. */
    public static function supportsGuests(): bool
    {
        static $supported = null;

        return $supported ??= (bool) Database::getInstance()->query("SHOW COLUMNS FROM athletes LIKE 'is_guest'")->fetchColumn();
    }

    /**
     * Convidado: inscrito por um dojo, mas de outro dojo ou clube. Exige a origem.
     *
     * @return array{0: array{is_guest: int, guest_origin: ?string}, 1: ?string} [campos, erro]
     */
    public static function guestFields(array $input): array
    {
        if (($input['is_guest'] ?? '0') !== '1') {
            return [['is_guest' => 0, 'guest_origin' => null], null];
        }

        $origin = trim((string) ($input['guest_origin'] ?? ''));

        if ($origin === '') {
            return [['is_guest' => 1, 'guest_origin' => null], 'Informe o dojo ou clube de origem do atleta convidado.'];
        }

        return [['is_guest' => 1, 'guest_origin' => mb_substr($origin, 0, 120)], null];
    }

    public static function find(int $id): ?array
    {
        $row = Database::getInstance()->query(
            "SELECT a.*, d.name AS dojo_name,
                    s.name AS style_name, g.belt_name, g.belt_color,
                    TIMESTAMPDIFF(YEAR, a.birth_date, CURDATE()) AS age
             FROM athletes a
             JOIN dojos d ON d.id = a.dojo_id
             LEFT JOIN martial_arts_styles s ON s.id = a.style_id
             LEFT JOIN graduations g ON g.id = a.graduation_id
             WHERE a.id = :id",
            ['id' => $id]
        )->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @param array{q?: string, graduation_id?: int, status?: string, dojo_id?: int, sort?: string, dir?: string} $filters
     */
    public static function search(array $filters): array
    {
        $where  = ['1 = 1'];
        $params = [];

        if (!empty($filters['dojo_id'])) {
            $where[] = 'a.dojo_id = :dojo_id';
            $params['dojo_id'] = (int) $filters['dojo_id'];
        }

        if (!empty($filters['q'])) {
            $where[] = '(a.full_name LIKE :q OR a.fgkirs_registration LIKE :q_reg)';
            $params['q']     = '%' . $filters['q'] . '%';
            $params['q_reg'] = '%' . $filters['q'] . '%';
        }

        if (!empty($filters['graduation_id'])) {
            $where[] = 'a.graduation_id = :graduation_id';
            $params['graduation_id'] = (int) $filters['graduation_id'];
        }

        if (!empty($filters['status']) && isset(self::STATUSES[$filters['status']])) {
            $where[] = 'a.status = :status';
            $params['status'] = $filters['status'];
        }

        return Database::getInstance()->query(
            "SELECT a.*, d.name AS dojo_name,
                    g.belt_name, g.belt_color, s.name AS style_name,
                    TIMESTAMPDIFF(YEAR, a.birth_date, CURDATE()) AS age
             FROM athletes a
             JOIN dojos d ON d.id = a.dojo_id
             LEFT JOIN graduations g ON g.id = a.graduation_id
             LEFT JOIN martial_arts_styles s ON s.id = a.style_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY " . self::orderBy((string) ($filters['sort'] ?? ''), (string) ($filters['dir'] ?? '')),
            $params
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Ordenação por lista branca (nunca interpola o que veio da URL). Empates e vazios terminam por nome. */
    private static function orderBy(string $sort, string $dir): string
    {
        $dir = $dir === 'desc' ? 'DESC' : 'ASC';

        $order = match ($sort) {
            // mais velho = nascimento mais antigo, então a direção da idade inverte a da data
            'age'    => 'a.birth_date ' . ($dir === 'ASC' ? 'DESC' : 'ASC'),
            'gender' => "a.gender $dir",
            'belt'   => "g.id IS NULL, s.name $dir, g.order_rank $dir",
            'weight' => "a.weight_kg IS NULL, a.weight_kg $dir",
            'status' => "FIELD(a.status, 'active', 'pending', 'inactive') $dir",
            default  => "a.full_name $dir",
        };

        return $sort === 'name' || !in_array($sort, self::SORTABLE, true)
            ? $order
            : $order . ', a.full_name ASC';
    }

    public static function documentTaken(string $digits, ?int $exceptId = null): bool
    {
        return (bool) Database::getInstance()->query(
            "SELECT 1 FROM athletes WHERE document = :doc AND id <> :id LIMIT 1",
            ['doc' => $digits, 'id' => $exceptId ?? 0]
        )->fetchColumn();
    }

    /**
     * Valida e normaliza o formulário. Devolve [dados, erro]; com erro, os dados não devem ser gravados.
     *
     * @return array{0: array, 1: ?string}
     */
    public static function validate(array $input, ?int $exceptId = null): array
    {
        $name = trim((string) ($input['full_name'] ?? ''));
        if ($name === '') {
            return [[], 'Informe o nome completo do atleta.'];
        }

        $birth = (string) ($input['birth_date'] ?? '');
        $date  = \DateTime::createFromFormat('Y-m-d', $birth);
        if (!$date || $date->format('Y-m-d') !== $birth) {
            return [[], 'Informe uma data de nascimento válida.'];
        }

        $years = (int) $date->diff(new \DateTime('today'))->format('%r%y');
        if ($date > new \DateTime('today') || $years > 100) {
            return [[], 'A data de nascimento está fora do intervalo aceito.'];
        }

        $gender = (string) ($input['gender'] ?? '');
        if (!isset(self::GENDERS[$gender])) {
            return [[], 'Selecione o sexo do atleta.'];
        }

        $document = preg_replace('/\D/', '', (string) ($input['document'] ?? ''));
        if ($document !== '') {
            if (!self::isValidCpf($document)) {
                return [[], 'CPF inválido. Confira os 11 dígitos.'];
            }
            if (self::documentTaken($document, $exceptId)) {
                return [[], 'Já existe um atleta cadastrado com este CPF.'];
            }
        }

        $weight = $input['weight_kg'] ?? '';
        $weight = $weight === '' ? null : (float) str_replace(',', '.', (string) $weight);
        if ($weight !== null && ($weight <= 0 || $weight >= 300)) {
            return [[], 'Informe um peso válido, em quilos.'];
        }

        $height = ($input['height_cm'] ?? '') === '' ? null : (int) $input['height_cm'];
        if ($height !== null && ($height < 50 || $height > 250)) {
            return [[], 'Informe uma altura válida, em centímetros.'];
        }

        $styleId       = !empty($input['style_id']) ? (int) $input['style_id'] : null;
        $graduationId  = !empty($input['graduation_id']) ? (int) $input['graduation_id'] : null;

        if ($graduationId !== null) {
            $graduation = Database::getInstance()->query(
                "SELECT style_id FROM graduations WHERE id = :id",
                ['id' => $graduationId]
            )->fetch(PDO::FETCH_ASSOC);

            if (!$graduation) {
                return [[], 'A graduação selecionada não existe.'];
            }

            // A faixa define o estilo: evita estilo e faixa de estilos diferentes
            if ($styleId !== null && (int) $graduation['style_id'] !== $styleId) {
                return [[], 'A graduação selecionada não pertence ao estilo escolhido.'];
            }
            $styleId = (int) $graduation['style_id'];
        }

        $status = (string) ($input['status'] ?? 'active');

        $guest = [];
        if (self::supportsGuests()) {
            [$guest, $guestError] = self::guestFields($input);
            if ($guestError !== null) {
                return [[], $guestError];
            }
        }

        return [$guest + [
            'full_name'           => $name,
            'birth_date'          => $birth,
            'gender'              => $gender,
            'document'            => $document !== '' ? $document : null,
            'style_id'            => $styleId,
            'graduation_id'       => $graduationId,
            'weight_kg'           => $weight,
            'height_cm'           => $height,
            'is_para_karate'      => ($input['is_para_karate'] ?? '0') === '1' ? 1 : 0,
            'fgkirs_registration' => trim((string) ($input['fgkirs_registration'] ?? '')) ?: null,
            'cbki_registration'   => trim((string) ($input['cbki_registration'] ?? '')) ?: null,
            'status'              => isset(self::STATUSES[$status]) ? $status : 'active',
        ], null];
    }

    public static function create(int $dojoId, int $createdBy, array $data, ?int $userId = null): int
    {
        $db = Database::getInstance();

        $guest = array_key_exists('is_guest', $data);

        $db->query(
            "INSERT INTO athletes
                (dojo_id, user_id, full_name, birth_date, gender, document, style_id, graduation_id,
                 weight_kg, height_cm, is_para_karate, fgkirs_registration, cbki_registration,
                 status, created_by" . ($guest ? ', is_guest, guest_origin' : '') . ")
             VALUES
                (:dojo_id, :user_id, :full_name, :birth_date, :gender, :document, :style_id, :graduation_id,
                 :weight_kg, :height_cm, :is_para_karate, :fgkirs_registration, :cbki_registration,
                 :status, :created_by" . ($guest ? ', :is_guest, :guest_origin' : '') . ")",
            array_merge($data, ['dojo_id' => $dojoId, 'user_id' => $userId, 'created_by' => $createdBy])
        );

        return (int) $db->lastInsertId();
    }

    public static function update(int $id, ?int $dojoId, array $data): void
    {
        $db = Database::getInstance();

        $params = array_merge($data, ['id' => $id]);
        $set    = '';

        // Só o admin troca o dojo; para o sensei o dojo continua o da sessão
        if ($dojoId !== null) {
            $set = 'dojo_id = :dojo_id, ';
            $params['dojo_id'] = $dojoId;
        }

        $db->query(
            "UPDATE athletes SET {$set}
                full_name = :full_name, birth_date = :birth_date, gender = :gender, document = :document,
                style_id = :style_id, graduation_id = :graduation_id, weight_kg = :weight_kg,
                height_cm = :height_cm, is_para_karate = :is_para_karate,
                fgkirs_registration = :fgkirs_registration, cbki_registration = :cbki_registration,
                status = :status" . (array_key_exists('is_guest', $data) ? ', is_guest = :is_guest, guest_origin = :guest_origin' : '') . "
             WHERE id = :id",
            $params
        );

        self::writeBackToProfile($id);
    }

    public static function setPhoto(int $id, ?string $filename): void
    {
        Database::getInstance()->query(
            "UPDATE athletes SET photo = :photo WHERE id = :id",
            ['photo' => $filename, 'id' => $id]
        );
    }

    public static function setStatus(int $id, string $status): void
    {
        Database::getInstance()->query(
            "UPDATE athletes SET status = :status WHERE id = :id",
            ['status' => $status, 'id' => $id]
        );
    }

    /**
     * Atleta com login: as telas antigas de inscrição ainda leem athlete_profiles
     * (até a fase 4), então a edição aqui é espelhada lá para os dois não divergirem.
     */
    private static function writeBackToProfile(int $id): void
    {
        $db = Database::getInstance();

        $a = $db->query("SELECT * FROM athletes WHERE id = :id", ['id' => $id])->fetch(PDO::FETCH_ASSOC);
        if (!$a || !$a['user_id']) {
            return;
        }

        $db->query(
            "UPDATE athlete_profiles
             SET birth_date = :birth_date, gender = :gender, is_para_karate = :para,
                 weight = :weight, height = :height, style_id = :style_id, graduation_id = :graduation_id,
                 fgkirs_registration = :fgkirs, cbki_registration = :cbki
             WHERE user_id = :user_id",
            [
                'birth_date'    => $a['birth_date'],
                'gender'        => $a['gender'],
                'para'          => $a['is_para_karate'],
                'weight'        => $a['weight_kg'],
                'height'        => $a['height_cm'],
                'style_id'      => $a['style_id'],
                'graduation_id' => $a['graduation_id'],
                // athlete_profiles guarda a matrícula como número
                'fgkirs'        => ctype_digit((string) $a['fgkirs_registration']) ? (int) $a['fgkirs_registration'] : null,
                'cbki'          => $a['cbki_registration'],
                'user_id'       => $a['user_id'],
            ]
        );
    }

    /**
     * Cria ou atualiza o atleta a partir do usuário aluno e do perfil dele, para os dois
     * cadastros não divergirem. Sem nascimento ou sexo M/F não há como criar (campos
     * obrigatórios em athletes), e nesse caso nada é feito.
     */
    public static function syncFromUser(int $userId, int $actorId): ?int
    {
        $db = Database::getInstance();

        $row = $db->query(
            "SELECT u.name, u.dojo_id, ap.*
             FROM users u
             JOIN athlete_profiles ap ON ap.user_id = u.id
             WHERE u.id = :id",
            ['id' => $userId]
        )->fetch(PDO::FETCH_ASSOC);

        if (!$row || !$row['dojo_id'] || !$row['birth_date'] || !in_array($row['gender'], ['M', 'F'], true)) {
            return null;
        }

        $fields = [
            'full_name'           => $row['name'],
            'birth_date'          => $row['birth_date'],
            'gender'              => $row['gender'],
            'style_id'            => $row['style_id'],
            'graduation_id'       => $row['graduation_id'],
            'weight_kg'           => $row['weight'],
            'height_cm'           => $row['height'],
            'is_para_karate'      => (int) $row['is_para_karate'],
            'fgkirs_registration' => $row['fgkirs_registration'] !== null ? (string) $row['fgkirs_registration'] : null,
            'cbki_registration'   => $row['cbki_registration'],
        ];

        $existingId = $db->query("SELECT id FROM athletes WHERE user_id = :id", ['id' => $userId])->fetchColumn();

        if ($existingId) {
            $db->query(
                "UPDATE athletes SET dojo_id = :dojo_id, full_name = :full_name, birth_date = :birth_date,
                    gender = :gender, style_id = :style_id, graduation_id = :graduation_id,
                    weight_kg = :weight_kg, height_cm = :height_cm, is_para_karate = :is_para_karate,
                    fgkirs_registration = :fgkirs_registration, cbki_registration = :cbki_registration
                 WHERE id = :id",
                array_merge($fields, ['dojo_id' => (int) $row['dojo_id'], 'id' => (int) $existingId])
            );

            return (int) $existingId;
        }

        return self::create((int) $row['dojo_id'], $actorId, $fields + ['document' => null, 'status' => 'active'], $userId);
    }
}
