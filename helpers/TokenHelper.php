<?php

namespace app\helpers;

use app\models\User;
use yii\web\UnauthorizedHttpException;

class TokenHelper
{
    /**
     * Verifica si el token de autenticación es válido.
     * @return bool
     * @throws UnauthorizedHttpException
     */
    public static function checkAuth(): bool
    {
        $headers = \Yii::$app->request->headers;
        $authHeader = $headers->get('Authorization');
        if(!$authHeader){
            throw new UnauthorizedHttpException('Token no existente');
        }
        if (preg_match('/^Bearer\s+(.*?)$/', $authHeader, $matches)) {
            $token = $matches[1];
            $user = User::findIdentityByAccessToken($token);
            if ($user) return true;
        }

        return false;
    }
}