<?php

namespace Controllers;

use Models\Post;
use Models\Dojo;
use Models\Event;
use Models\FederationProfile;

class HomeController extends Controller
{
    public function underConstruction(): void
    {
        $this->view('under_construction');
    }

    public function index(): void
    {
        try {
            $news    = Post::getLatest(3);
            $dojos   = Dojo::getAllActive();
            $events  = Event::getUpcoming(5);
            $profile = FederationProfile::get();
        } catch (\Exception $e) {
            error_log('HomeController error: ' . $e->getMessage());
            $news = $dojos = $events = [];
            $profile = [];
        }

        $this->view('home', [
            'news'    => $news,
            'dojos'   => $dojos,
            'events'  => $events,
            'profile' => $profile,
        ]);
    }
}
