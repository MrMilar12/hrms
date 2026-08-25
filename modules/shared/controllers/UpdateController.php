<?php

class UpdateController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        $this->view('shared', 'updates', [
            'pageTitle' => "What's New",
            'releases' => SystemRelease::published(),
        ]);
    }

    public function acknowledge(): void
    {
        Auth::requireLogin();
        $this->requireCsrf();
        $releaseIds = $_POST['release_ids'] ?? [];
        if (!is_array($releaseIds)) $releaseIds = [$releaseIds];
        SystemRelease::acknowledge((int) Auth::userId(), $releaseIds);
        $this->json(['success' => true]);
    }
}
