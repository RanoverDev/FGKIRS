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

    /**
     * Apaga de vez (não há lixeira): serve para o certificado impresso por engano. Admin e sensei do
     * dojo do atleta; não existe edição de certificado emitido.
     */
    public function delete(int $id): void
    {
        $this->authorizeDojo(null);

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->json(['ok' => false, 'message' => 'Sessão expirada. Recarregue a página e tente de novo.'], 419);
        }

        $certificate = $this->certificates->find($id);

        if (!$certificate) {
            $this->json(['ok' => false, 'message' => 'Certificado não encontrado.'], Auth::isAdmin() ? 404 : 403);
        }

        $this->authorizeDojo((int) $certificate['dojo_id']);

        $this->certificates->delete($id);
        error_log(sprintf(
            'Certificate %d (athlete %d, %s) deleted by user %d',
            $id,
            $certificate['athlete_id'],
            $certificate['graduation_label'],
            (int) Auth::id()
        ));

        $this->json(['ok' => true]);
    }

    private function authorizeAthlete(int $athleteId): void
    {
        $athlete = Auth::isAdmin() ? null : Athlete::find($athleteId);

        $this->authorizeDojo($athlete ? (int) $athlete['dojo_id'] : 0);
    }

    /**
     * Versão JSON de DojoScope::assertDojo (o pop-up consome estas rotas via fetch).
     * Sem dojo informado (null) só confere o papel; dojo inexistente (0) nega ao sensei.
     */
    private function authorizeDojo(?int $dojoId): void
    {
        if (!Auth::authorize(['admin', 'sensei'])) {
            $this->json(['ok' => false, 'message' => 'Acesso negado.'], 403);
        }

        if (Auth::isAdmin() || $dojoId === null) {
            return;
        }

        $current = DojoScope::senseiDojoId();

        if ($current === null || $dojoId !== $current) {
            $this->json(['ok' => false, 'message' => 'Você não tem permissão para acessar este atleta.'], 403);
        }
    }
}
