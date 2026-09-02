<?php

class SettingsController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        $this->view('shared', 'settings', ['pageTitle' => 'Appearance Settings', 'settingsDrawer' => false]);
    }

    public function drawer(): void
    {
        Auth::requireLogin();
        $this->view('shared', 'settings', ['pageTitle' => 'Appearance Settings', 'settingsDrawer' => true]);
    }
}
