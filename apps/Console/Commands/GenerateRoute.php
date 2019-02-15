<?php

namespace App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateRoute extends Command
{
    protected static $defaultName = 'app:generate-route';

    protected function configure()
    {

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    	$paths = glob(app_path('Http/Controllers/*'));

        foreach ($paths as $key => $path) {
            if (!is_dir($path) || in_array(basename($path), ['Api', 'Auth'])) {
                unset($paths[$key]);
            }
        }

        $route = "<?php\n\n";
        foreach ($paths as $path) {
            $group = basename($path);
            $controllers = glob($path . '/*.php');

            $route .= 'Route::namespace(\'' . $group . '\')->group(function() {'."\n";
            foreach ($controllers as $controller) {
                $route .= '    Route::any(\'' . snake_case(basename($controller, 'Controller.php')) . '.php\', \'' . basename($controller, '.php') . '@index\');'."\n";
            }
            $route .= '});'."\n";
        }

        file_put_contents(base_path('routes/web.php'), $route);
    }
}
