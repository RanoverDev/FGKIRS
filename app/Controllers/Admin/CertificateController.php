<?php

namespace Controllers\Admin;

use Controllers\Controller;
use Helpers\Auth;
use Helpers\CertificateBackground;
use Helpers\Csrf;
use Helpers\DojoScope;
use Models\Athlete;
use Repositories\CertificateRepository;
use Services\CertificateService;

/**
 * Certificado de graduação do atleta. Admin emite para qualquer dojo; o sensei só para os
 * atletas do dojo da sessão (403 para o resto, sem confirmar que o registro existe).
 */
class CertificateController extends Controller
{
    public function __construct(
        private CertificateService $service = new CertificateService(),
        private CertificateRepository $certificates = new CertificateRepository()
    ) {
    }

    /** Texto inicial do pop-up (JSON). */
    public function draft(int $athleteId): void
    {
        $this->authorizeAthlete($athleteId);

        try {
            $this->json(['ok' => true] + $this->service->draft($athleteId));
        } catch (\DomainException $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function store(int $athleteId): void
    {
        $this->authorizeAthlete($athleteId);

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->json(['ok' => false, 'message' => 'Sessão expirada. Recarregue a página e tente de novo.'], 419);
        }

        try {
            $id = $this->service->issue(
                $athleteId,
                (string) ($_POST['body_html'] ?? ''),
                (string) ($_POST['graduation_label'] ?? ''),
                (string) ($_POST['graduation_date'] ?? ''),
                (int) Auth::id()
            );
        } catch (\DomainException $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        } catch (\PDOException $e) {
            error_log('CertificateController::store error: ' . $e->getMessage());
            $this->json(['ok' => false, 'message' => 'Não foi possível gerar o certificado. Tente novamente.'], 500);
        }

        $this->json(['ok' => true, 'url' => '/fgkirs-admin/certificates/' . $id]);
    }

    /** Página de impressão (A4 paisagem proporcional ao fundo), aberta sempre a partir do histórico gravado. */
    public function show(int $id): void
    {
        if (!Auth::authorize(['admin', 'sensei'])) {
            $this->redirect('/fgkirs-admin');
        }

        $certificate = $this->certificates->find($id);

        if (!$certificate) {
            if (Auth::isAdmin()) {
                $_SESSION['error'] = 'Certificado não encontrado.';
                $this->redirect('/fgkirs-admin/athletes');
            }
            DojoScope::deny();
        }

        DojoScope::assertDojo((int) $certificate['dojo_id']);

        $this->view('admin/certificates/show', [
            'certificate'   => $certificate,
            'backgroundUrl' => CertificateBackground::url($certificate['certificate_background']),
            'pageTitle'     => 'Certificado – ' . $certificate['athlete_name'],
        ]);
    }

    /** Versão JSON de DojoScope::assertDojo: o pop-up consome estas rotas via fetch. */
    private function authorizeAthlete(int $athleteId): void
    {
        if (!Auth::authorize(['admin', 'sensei'])) {
            $this->json(['ok' => false, 'message' => 'Acesso negado.'], 403);
        }

        if (Auth::isAdmin()) {
            return;
        }

        $athlete = Athlete::find($athleteId);
        $dojoId  = DojoScope::senseiDojoId();

        if (!$athlete || $dojoId === null || (int) $athlete['dojo_id'] !== $dojoId) {
            $this->json(['ok' => false, 'message' => 'Você não tem permissão para acessar este atleta.'], 403);
        }
    }
}
