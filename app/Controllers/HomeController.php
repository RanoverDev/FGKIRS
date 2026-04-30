<?php

namespace Controllers;

use Models\Post;
use Models\Dojo;
use Models\Event;

class HomeController extends Controller
{
    public function underConstruction(): void
    {
        $this->view('under_construction');
    }

    public function index(): void
    {
        try {
            $news = Post::getLatest(3);
            $dojos = Dojo::getAllActive();
            $events = Event::getUpcoming(5);
        } catch (\Exception $e) {
            error_log('HomeController error: ' . $e->getMessage());
            $news = $dojos = $events = [];
        }

        $this->view('home', [
            'news' => $news,
            'dojos' => $dojos,
            'events' => $events,
        ]);
    }
}
