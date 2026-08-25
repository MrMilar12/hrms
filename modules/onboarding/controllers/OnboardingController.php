<?php
// Onboarding controller: mandatory welcome screen shown before a new user's PDS is complete.

class OnboardingController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();

        $percent = Auth::employeeId() ? (new Pds())->completionPercent(Auth::employeeId()) : 0;

        $this->view('onboarding', 'welcome', [
            'completionPercent' => $percent,
        ]);
    }
}
