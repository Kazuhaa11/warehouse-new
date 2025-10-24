<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class TestSession extends BaseController
{
    public function index()
    {
        $session = session();

        $session->set('foo', 'bar');
        $value = $session->get('foo');

        echo "<h3>Session Test</h3>";
        echo "<p>Session OK ✅</p>";
        echo "<p><strong>Saved key:</strong> foo</p>";
        echo "<p><strong>Value:</strong> {$value}</p>";
        echo "<p>Session save path: <strong>" . ini_get('session.save_path') . "</strong></p>";
    }
}
