<?php

require_once '../vendor/autoload.php';

use Sarasa\Core\FrontController;
use Sarasa\Core\Template;
use Sarasa\Core\AjaxResponse;
use Sarasa\Core\CustomException;

session_start();

try {
    $action = FrontController::route();

    $controllername = FrontController::$bundle . '\Controllers\\' . FrontController::$controller;
    $controllereval = '$controller = new ' . $controllername . '();';
    eval($controllereval);

    if (isset($_SERVER['HTTP_AJAX_FUNCTION'])) {
        try {
            $function = $_SERVER['HTTP_AJAX_FUNCTION'];
            $parametros = $_REQUEST;
            $url = $_SERVER['HTTP_AJAX_URL'];

            if (isset($parametros['debughash'])) {
                $controller->parenthash = $parametros['debughash'];
            }

            $objResponse = new AjaxResponse();
            $objResponse->script('stoploading();');

            if ($function == 'index') {
                throw new CustomException('Nombre de la función inválido.');
            }

            if (!method_exists($controllername, $function)) {
                throw new CustomException("No se encontró la función");
            }

            $func  = '$controller->' . $function . '($objResponse,$parametros);';
            eval($func);

            if (!FrontController::config('production') && $function != 'debugbar') {
                $objResponse->script('loaddebugbar("' . FrontController::$debugpath  . '/' . FrontController::$mtime . '");');
            }
        } catch (Exception $e) {
            $objResponse->script('error("' . addslashes(str_replace("\n", "", nl2br($e))) . '");');
        }

        echo $objResponse->toJSON();
    } else {
        try {
            $func = '$controller->' . $action . '();';
            if (!method_exists($controllername, $action)) {
                throw new CustomException("No se encontró la acción", 404);
            }
            eval($func);
        } catch (Exception $e) {
            FrontController::handlePageException($e);
        }
    }

} catch (Exception $e) {
    Template::error500($e);
}
