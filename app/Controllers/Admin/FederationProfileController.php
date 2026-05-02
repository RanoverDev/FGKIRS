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
            'whatsapp' => trim($_POST['whatsapp'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'state' => strtoupper(trim($_POST['state'] ?? '')),
            'zip_code' => trim($_POST['zip_code'] ?? ''),
            'facebook' => trim($_POST['facebook'] ?? ''),
            'instagram' => trim($_POST['instagram'] ?? ''),
        ]);

        $_SESSION['success'] = 'Perfil da federação atualizado com sucesso!';
        header('Location: /fgkirs-admin/federation-profile');
        exit;
    }
}
