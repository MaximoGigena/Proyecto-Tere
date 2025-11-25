<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Router;

class DebugTelegramRoutes extends Command
{
    protected $signature = 'debug:telegram-routes';
    protected $description = 'Debug Telegram routes and middlewares';

    public function handle(Router $router)
    {
        $this->info("🔍 Debugging Telegram routes...");
        
        $routes = $router->getRoutes();
        
        foreach ($routes as $route) {
            if (strpos($route->uri, 'telegram') !== false) {
                $this->info("=== ROUTE: {$route->uri} ===");
                $this->info("Methods: " . implode(', ', $route->methods));
                $this->info("Action: " . $route->getActionName());
                $this->info("Middleware: " . implode(', ', $route->middleware()));
                $this->info("---");
            }
        }
    }
}