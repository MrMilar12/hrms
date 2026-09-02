<?php

class AiController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        $this->view('ai', 'chat', ['pageTitle' => 'HRMS AI Assistant']);
    }

    public function chat(): void
    {
        Auth::requireLogin();
        $message = trim((string) $this->input('message', ''));
        $history = json_decode((string) $this->input('history', '[]'), true);
        if (!is_array($history)) $history = [];
        if ($message === '' || strlen($message) > 4000) $this->json(['success'=>false,'error'=>'Enter a message up to 4,000 characters.'],422);
        try { $this->json(['success'=>true,'reply'=>(new LocalCloudLlamaService())->chat($message, $this->authorizedContext($message) . $this->liveWeatherContext($message), $history)]); }
        catch (RuntimeException $e) { $this->json(['success'=>false,'error'=>$e->getMessage()],502); }
    }

    private function liveWeatherContext(string $message): string
    {
        if (!preg_match('/\b(weather|forecast|temperature|rain|raining)\b/i', $message)) return '';
        if (!preg_match('/\b(?:in|at|for)\s+([A-Za-z][A-Za-z .-]{1,60})/i', $message, $match)) {
            return "\nLive weather: Ask the user to provide a city or location.";
        }
        $location = trim($match[1], " .,-");
        $geoUrl = 'https://geocoding-api.open-meteo.com/v1/search?count=1&language=en&format=json&name=' . rawurlencode($location);
        $geo = $this->getJson($geoUrl);
        $place = $geo['results'][0] ?? null;
        if (!$place) return "\nLive weather: Location not found for {$location}.";
        $forecastUrl = 'https://api.open-meteo.com/v1/forecast?latitude=' . rawurlencode((string) $place['latitude']) . '&longitude=' . rawurlencode((string) $place['longitude']) . '&current=temperature_2m,relative_humidity_2m,apparent_temperature,precipitation,weather_code,wind_speed_10m&daily=temperature_2m_max,temperature_2m_min,precipitation_probability_max&forecast_days=3&timezone=auto';
        $forecast = $this->getJson($forecastUrl);
        if (!$forecast || empty($forecast['current'])) return "\nLive weather: Live weather service is unavailable.";
        $current = $forecast['current'];
        $daily = $forecast['daily'] ?? [];
        return "\nLive weather for {$place['name']}, {$place['country']}: temperature {$current['temperature_2m']}°C, feels like {$current['apparent_temperature']}°C, humidity {$current['relative_humidity_2m']}%, wind {$current['wind_speed_10m']} km/h, precipitation {$current['precipitation']} mm. Forecast highs: " . implode(', ', $daily['temperature_2m_max'] ?? []) . "°C. This data is live and should be clearly identified as a forecast.";
    }

    private function getJson(string $url): array
    {
        $ch = curl_init($url); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>8, CURLOPT_HTTPHEADER=>['Accept: application/json']]);
        $raw = curl_exec($ch); curl_close($ch);
        $data = json_decode((string) $raw, true);
        return is_array($data) ? $data : [];
    }

    private function authorizedContext(string $message = ''): string
    {
        $pdo = Database::getInstance();
        $lines = ['Current user: ' . Auth::displayName() . ' (' . (Auth::roleName() ?? 'User') . ')'];
        $employeeId = Auth::employeeId();
        if ($employeeId) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM task_assignments WHERE employee_id=? AND status NOT IN ('Done','Cancelled')");
            $stmt->execute([$employeeId]);
            $lines[] = 'Current user open tasks: ' . (int) $stmt->fetchColumn();
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM accomplishments WHERE employee_id=? AND status='For Review'");
            $stmt->execute([$employeeId]);
            $lines[] = 'Current user accomplishments awaiting review: ' . (int) $stmt->fetchColumn();
        }
        if (Auth::can('report.view')) {
            $lines[] = 'Organization employees: ' . (int) $pdo->query('SELECT COUNT(*) FROM employees')->fetchColumn();
            $lines[] = 'Organization departments: ' . (int) $pdo->query('SELECT COUNT(*) FROM departments')->fetchColumn();
            $lines[] = 'Open assignments organization-wide: ' . (int) $pdo->query("SELECT COUNT(*) FROM task_assignments WHERE status NOT IN ('Done','Cancelled')")->fetchColumn();
            $lines[] = 'Accomplishments awaiting review organization-wide: ' . (int) $pdo->query("SELECT COUNT(*) FROM accomplishments WHERE status='For Review'")->fetchColumn();
        }
        if (Auth::can('user.manage')) $lines[] = 'Active user accounts: ' . (int) $pdo->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();
        $term = trim(preg_replace('/[^\p{L}\p{N} ._@-]+/u', ' ', $message));
        if (mb_strlen($term) >= 2) {
            $like = '%' . $term . '%';
            $nameParts = preg_split('/\s+/u', $term, -1, PREG_SPLIT_NO_EMPTY);
            $nameLike = '%' . implode('%', $nameParts) . '%';
            if (Auth::can('report.view')) {
                $stmt = $pdo->prepare("SELECT CONCAT('Employee: ', COALESCE(NULLIF(TRIM(CONCAT_WS(' ',pi.first_name,pi.middle_name,pi.surname)),''),e.employee_number), ' · ', COALESCE(d.name,'Unassigned')) FROM employees e LEFT JOIN pds_personal_info pi ON pi.employee_id=e.id LEFT JOIN departments d ON d.id=e.department_id WHERE CONCAT_WS(' ',pi.first_name,pi.middle_name,pi.surname,e.employee_number) LIKE ? OR pi.first_name LIKE ? OR pi.surname LIKE ? LIMIT 8");
                $stmt->execute([$nameLike, $like, $like]); foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $row) $lines[] = $row;
                $stmt = $pdo->prepare("SELECT CONCAT('Task: ',t.title,' · status: ',ta.status) FROM task_assignments ta JOIN tasks t ON t.id=ta.task_id WHERE t.title LIKE ? LIMIT 8");
                $stmt->execute([$like]); foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $row) $lines[] = $row;
                $stmt = $pdo->prepare("SELECT CONCAT('Accomplishment: ',a.title,' · status: ',a.status) FROM accomplishments a WHERE a.title LIKE ? LIMIT 8");
                $stmt->execute([$like]); foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $row) $lines[] = $row;
            } elseif ($employeeId) {
                $stmt = $pdo->prepare("SELECT CONCAT('My task: ',t.title,' · status: ',ta.status) FROM task_assignments ta JOIN tasks t ON t.id=ta.task_id WHERE ta.employee_id=? AND t.title LIKE ? LIMIT 8");
                $stmt->execute([$employeeId, $like]); foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $row) $lines[] = $row;
                $stmt = $pdo->prepare("SELECT CONCAT('My accomplishment: ',a.title,' · status: ',a.status) FROM accomplishments a WHERE a.employee_id=? AND a.title LIKE ? LIMIT 8");
                $stmt->execute([$employeeId, $like]); foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $row) $lines[] = $row;
            }
        }
        return implode("\n", $lines);
    }
}
