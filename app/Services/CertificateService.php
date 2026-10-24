<?php

namespace Services;

use Domain\Certificate\CertificateText;
use Helpers\CertificateBackground;
use Helpers\RichText;
use Repositories\CertificateRepository;

class CertificateService
{
    public function __construct(private CertificateRepository $certificates = new CertificateRepository())
    {
    }

    /**
     * Texto inicial do pop-up, montado com o cadastro atual do atleta.
     *
     * @return array{label: string, date: string, body_html: string, style_name: string}
     * @throws \DomainException quando falta algo no cadastro; a mensagem é exibida ao usuário
     */
    public function draft(int $athleteId): array
    {
        $ctx = $this->context($athleteId);
        $label = CertificateText::graduationLabel($ctx['belt_name']);
        $today = new \DateTimeImmutable('today');

        return [
            'label'      => $label,
            'date'       => $today->format('Y-m-d'),
            'style_name' => $ctx['style_name'],
            'body_html'  => CertificateText::defaultBody(
                $ctx['full_name'],
                (string) $ctx['gender'],
                $label,
                $ctx['style_name'],
                $today,
                (string) $ctx['dojo_city'],
                (string) $ctx['dojo_state'],
                $ctx['dojo_name']
            ),
        ];
    }

    /**
     * Valida, sanitiza e grava o certificado emitido. O texto editado é guardado como foi emitido:
     * mudar o cadastro depois não altera certificados antigos.
     *
     * @throws \DomainException com mensagem para o usuário
     */
    public function issue(int $athleteId, string $bodyHtml, string $label, string $date, int $userId): int
    {
        $ctx = $this->context($athleteId);

        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date) {
            throw new \DomainException('Informe uma data de graduação válida.');
        }
        if ($parsed > new \DateTimeImmutable('today')) {
            throw new \DomainException('A data da graduação não pode ser futura.');
        }

        $label = trim(strip_tags($label));
        if ($label === '' || mb_strlen($label) > 60) {
            throw new \DomainException('Informe a graduação (até 60 caracteres) que aparece ao lado do kanji.');
        }

        $body = RichText::sanitize($bodyHtml);
        if (RichText::plainText($body) === '') {
            throw new \DomainException('O texto do certificado não pode ficar vazio.');
        }

        return $this->certificates->create([
            'athlete_id'       => $athleteId,
            'style_id'         => (int) $ctx['style_id'],
            'graduation_id'    => (int) $ctx['graduation_id'],
            'graduation_label' => $label,
            'graduation_date'  => $date,
            'body_html'        => $body,
            'issued_by'        => $userId,
        ]);
    }

    /** @throws \DomainException */
    private function context(int $athleteId): array
    {
        $ctx = $this->certificates->context($athleteId);

        if (!$ctx) {
            throw new \DomainException('Atleta não encontrado.');
        }
        if (empty($ctx['graduation_id']) || empty($ctx['style_id'])) {
            throw new \DomainException('O atleta não tem graduação cadastrada. Atualize o cadastro do atleta e tente de novo.');
        }
        if (empty($ctx['certificate_background']) || !is_file(CertificateBackground::directory() . '/' . $ctx['certificate_background'])) {
            throw new \DomainException(
                'O estilo ' . $ctx['style_name'] . ' ainda não tem a arte de fundo do certificado. '
                . 'Peça ao Presidente para enviá-la em Estilos.'
            );
        }

        return $ctx;
    }
}
