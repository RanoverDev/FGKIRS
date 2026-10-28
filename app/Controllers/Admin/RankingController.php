<?php

namespace Controllers\Admin;

use Controllers\Controller;
use Helpers\Auth;
use Repositories\BracketRepository;

/** Ranking da federação: pontos dos resultados de todos os campeonatos, por atleta e por dojo. */
class RankingController extends Controller
{
    public function index(): void
    {
        if (!Auth::authorize(['admin', 'sensei'])) {
            $this->redirect('/login');
        }

        $repository = new BracketRepository();
        $ready      = $repository->tablesExist();

        $filters = [
            'year'     => (int) ($_GET['year'] ?? 0),
            'modality' => in_array($_GET['modality'] ?? '', ['kata', 'kumite'], true) ? $_GET['modality'] : '',
        ];

        $this->view('admin/brackets/ranking', [
            'ready'    => $ready,
            'filters'  => $filters,
            'years'    => $ready ? $repository->rankingYears() : [],
            'athletes' => $ready ? $repository->athleteRanking($filters) : [],
            'dojos'    => $ready ? $repository->dojoRanking($filters) : [],
            'pageTitle' => 'Ranking',
        ]);
    }
}
