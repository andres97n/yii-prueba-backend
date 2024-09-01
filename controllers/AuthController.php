<?php

namespace app\controllers;

use Yii;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UnauthorizedHttpException;

use app\models\User;

/**
 * AuthorController implementa acciones para manejar la sesión del usuario
 *
 * @author Andrés Novillo <anovillo@libelulasoft.com>
 * @since 0.0.1
 */
class AuthController extends Controller
{

    /**
     * Rutas que maneja el controlador
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'login'    => ['POST'],
                    'password' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Retorna una respuesta en base a la estructura para las Api's
     * @param $data
     * @param string $message
     * @param string $status
     * @param int $statusCode
     * @return Response
     */
    public function getHttpResponse($data, string $message, string $status, int $statusCode = 200): Response
    {
        Yii::$app->response->statusCode = $statusCode;
        return $this->asJson([
            'response' => $data,
            'message' => $message,
            'status' => $status
        ]);
    }

    /**
     * Configura la zona horaria y desactiva la validación CSRF antes de ejecutar cualquier acción.
     * @param $action
     * @return bool
     * @throws BadRequestHttpException
     */
    public function beforeAction($action) {
        date_default_timezone_set("America/Guayaquil");
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    /**
     * Realiza el inicio de sesión del usuario y retorna el token de acceso
     * @return Response
     * @throws UnauthorizedHttpException
     */
    public function actionLogin(): Response
    {
        $input = file_get_contents("php://input");
        $data = json_decode($input);
        $username = $data->username;
        $password = $data->password;
        $user = User::findByUsername($username);

        if ($user && $user->validatePassword($password)) {
            $user->generateAuthKey();
            return $this->getHttpResponse(
                $user->auth_key,
                "Token generado correctamente",
                'ok',
            );
        }
        throw new UnauthorizedHttpException('Credenciales incorrectas');
    }

    /**
     * Obtiene una contraseña y devuelve una contraseña de tipo Hash
     * @return Response
     */
    public function actionPassword(): Response
    {
        $input = file_get_contents("php://input");
        $data = json_decode($input);
        $password = $data->password;
        try {
            $hashedPassword = Yii::$app->security->generatePasswordHash($password);
            return $this->getHttpResponse(
                $hashedPassword,
                "Contraseña generada correctamente",
                'ok',
            );
        } catch (\Exception $error) {
            return $this->getHttpResponse(
                null,
                $error->getMessage(),
                'error',
                400
            );
        }
    }
}