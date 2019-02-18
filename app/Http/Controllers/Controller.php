<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Execute an action on the controller.
     *
     * @param  string $method
     * @param  array $parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function callAction($method, $parameters)
    {
        if (method_exists($this, 'initialize')) {
            $response = call_user_func([$this, 'initialize']);
            if (!is_null($response)) {
                return $response;
            }
        }

        return call_user_func_array([$this, $method], $parameters);
    }

    /**
     * 获取当前模块名
     *
     * @return string
     */
    protected function getCurrentModuleName()
    {
        return $this->currentAction()['module'];
    }

    /**
     * 获取当前控制器名
     *
     * @return string
     */
    protected function getCurrentControllerName()
    {
        return $this->currentAction()['controller'];
    }

    /**
     * 获取当前方法名
     *
     * @return string
     */
    protected function getCurrentMethodName()
    {
        return $this->currentAction()['method'];
    }

    /**
     * 获取当前控制器与方法
     *
     * @return array
     */
    protected function currentAction()
    {
        $action = request()->route()->getAction();

        list($_1, $_2, $_3, $module, $actions) = explode('\\', $action['controller']);

        list($controller, $action) = explode('@', $actions);

        return ['module' => $module, 'controller' => $controller, 'method' => $action];
    }
}
