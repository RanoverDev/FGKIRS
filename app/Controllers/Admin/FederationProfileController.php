<?php

namespace Controllers\Admin;

use Controllers\Controller;
use Helpers\Auth;
use Models\FederationProfile;

class FederationProfileController extends Controller
{
    public function edit(): void
    {
        if (!Auth::authorize(['admin'])) {
            header('Location: /fgkirs-admin');
            exit;
        }

        $profile = FederationProfile::get();
        $this->view('admin/federation_profile/form', ['profile' => $profile]);
    }

    public function update(): void
    {
        if (!Auth::authorize(['admin'])) {
            header('Location: /fgkirs-admin');
            exit;
        }

        FederationProfile::save([
            'legal_name' => trim($_POST['legal_name'] ?? '') ?: null,
            'cnpj'     => trim($_POST['cnpj'] ?? '') ?: null,
            'website'  => trim($_POST['website'] ?? '') ?: null,
            'whatsapp' => preg_replace('/\D/', '', $_POST['whatsapp'] ?? '') ?: null,
            'phone'    => preg_replace('/\D/', '', $_POST['phone']    ?? '') ?: null,
            'email'    => trim($_POST['email']   ?? ''),
            'address'  => trim($_POST['address'] ?? ''),
            'city'     => trim($_POST['city']    ?? ''),
            'state'    => strtoupper(trim($_POST['state'] ?? '')),
            'zip_code' => preg_replace('/\D/', '', $_POST['zip_code'] ?? '') ?: null,
            'facebook' => trim($_POST['facebook'] ?? ''),
            'instagram' => trim($_POST['instagram'] ?? ''),
        ]);

        $_SESSION['success'] = 'Perfil da federação atualizado com sucesso!';
        header('Location: /fgkirs-admin/federation-profile');
        exit;
    }
}
