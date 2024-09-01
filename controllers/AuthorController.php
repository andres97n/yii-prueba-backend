<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\web\UnauthorizedHttpException;

use app\constants\GlobalConstants;
use app\helpers\TokenHelper;
use app\models\Book;
use app\models\Author;

/**
 * AuthorController implementa acciones para manejar un CRUD del modelo Autor
 *
 * @author Andrés Novillo <anovillo@libelulasoft.com>
 * @since 0.0.1
 */
class AuthorController extends Controller
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
                    'index'  => ['GET', 'HEAD'],
                    'view'   => ['GET', 'HEAD'],
                    'create' => ['POST'],
                    'update' => ['PUT', 'PATCH'],
                    'delete' => ['DELETE'],
                ],
            ],
        ];
    }


    /**
     * Antes de ejecutar una acción, configura la zona horaria y verifica la autenticación del usuario.
     * @param $action
     * @return bool
     * @throws UnauthorizedHttpException
     * @throws yii\web\BadRequestHttpException
     */
    public function beforeAction($action): bool {
        date_default_timezone_set("America/Guayaquil");
        $this->enableCsrfValidation = false;
        if (!TokenHelper::checkAuth()) {
            throw new UnauthorizedHttpException('Token no válido o caducado');
        }
        return parent::beforeAction($action);
    }

    /**
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
     * Retorna todos los autores activos
     * @return Response
     */
    public function actionIndex(): Response
    {
        try {
            $authors = Author::getAuthors();

            return $this->getHttpResponse(
                $authors,
                'Autores encontrados',
                'ok'
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

    /**
     * Busca un autor por su _id
     * @return Response
     */
    public function actionView(): Response
    {
        $input = file_get_contents("php://input");
        $author_response = json_decode($input);

        if (!($author_response->_id ?? null)) {
            return $this->getHttpResponse(
                null,
                "Se debe mandar el campo '_id'",
                'error',
                404
            );
        }
        if (!($author = Author::getAuthorById($author_response->_id))) {
            return $this->getHttpResponse(
                null,
                'No se encontró el autor referenciado',
                'error',
                404
            );
        }

        return $this->getHttpResponse(
            $author,
            'Autor encontrado',
            'ok'
        );
    }

    /**
     * Crea un nuevo autor y también agrega libros por sus _id's
     * @return Response
     */
    public function actionCreate(): Response
    {
        $input = file_get_contents("php://input");
        $data = json_decode($input);
        if ($this->request->isPost) {
            $author = new Author($data);
            $isValidModel = $author->validate();

            if (!$isValidModel) {
                foreach ($author->errors as $error => $value) {
                    if ($error !== 'state') {
                        return $this->getHttpResponse(
                            null,
                            $value[0],
                            'error',
                            404
                        );
                    }
                }
            }

            $author->state = GlobalConstants::STATE_ACTIVO;
            $books = [];

            try {
                if (is_array($data->books ?? null)) {
                    foreach ($data->books as $bookId) {
                        $bookModel = Book::getBookById($bookId);
                        if (!is_null($bookModel)) {
                            unset($bookModel['authors']);
                            $books[] = $bookModel->toArray();
                        }
                    }
                }

                $author->books = $books;

                if ($author->save()) {
                    return $this->getHttpResponse(
                        $author,
                        'Autor agregado con éxito',
                        'ok',
                        201
                    );

                } else {
                    $errors = $author->errors;
                    return $this->getHttpResponse(
                        $errors,
                        'No se creó el Autor',
                        'error',
                        400
                    );
                }
            } catch (\Exception $error) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return $this->getHttpResponse(
                    null,
                    $error->getMessage(),
                    'error',
                    400
                );
            }
        } else {
            return $this->getHttpResponse(
                [],
                'Método no permitido',
                'error',
                405
            );
        }
    }

    /**
     * Actualiza el autor y también agrega libros por sus _id's
     * @return Response
     */
    public function actionUpdate(): Response
    {;
        if ($this->request->isPut) {
            $input = file_get_contents("php://input");
            $data_update = json_decode($input);

            if (!($data_update->_id ?? null)) {
                return $this->getHttpResponse(
                    null,
                    "Se requiere el '_id' del autor",
                    'error',
                    404
                );
            }

            $modelValidated = new Author($data_update);
            $isValidModel = $modelValidated->validate();
            if (!$isValidModel) {
                foreach ($modelValidated->errors as $error => $value) {
                    if ($error !== 'state') {
                        return $this->getHttpResponse(
                            null,
                            $value[0],
                            'error',
                            404
                        );
                    }
                }
            }

            $author = Author::findOne(['_id' => $data_update->_id]);
            if ($author === null) {
                return $this->getHttpResponse(
                    null,
                    "No se encontró el autor referenciado",
                    'error',
                    404
                );
            }

            $author->fullname = $modelValidated->fullname;
            $author->birthdate = $modelValidated->birthdate;

            try {
                if (is_array($data_update->books ?? null)) {
                    $books = [];
                    foreach ($data_update->books as $bookId) {
                        $bookModel = Book::getBookById($bookId);
                        if (!is_null($bookModel)) {
                            unset($bookModel['authors']);
                            $books[] = $bookModel->toArray();
                        }
                    }
                    $author->books = $books;
                }

                if ($author->save()) {
                    return $this->getHttpResponse(
                        $author,
                        'Autor editado con éxito',
                        'ok',
                        200
                    );
                } else {
                    return $this->getHttpResponse(
                        $author->getErrors(),
                        'No se editó el autor',
                        'error',
                        400
                    );
                }
            } catch (\Exception $error) {
                return $this->getHttpResponse(
                    null,
                    $error->getMessage(),
                    'error',
                    400
                );
            }
        } else {
            return $this->getHttpResponse(
                [],
                'Método no permitido',
                'error',
                405
            );
        }
    }

    /**
     * Elimina el autor y a todas sus referencias en la colección Libro
     * @return Response
     */
    public function actionDelete(): Response
    {
        if ($this->request->isDelete) {
            $input = file_get_contents("php://input");
            $data_delete = json_decode($input);

            if (!($data_delete->_id ?? null)) {
                return $this->getHttpResponse(
                    null,
                    "Se debe enviar el '_id' del autor",
                    'error',
                    404
                );
            }

            try {
                $author = Author::getAuthorById($data_delete->_id);
                if (!($author ?? null)) {
                    return $this->getHttpResponse(
                        null,
                        "No se encontró el autor referenciado",
                        'error',
                        404
                    );
                }

                $author->state = GlobalConstants::STATE_DELETE;

                if ($author->save()) {
                    $booksToClear = Book::find()->where([
                        'authors' => [
                            '$elemMatch' => ['_id' => $data_delete->_id]
                        ]
                    ])->all();

                    foreach ($booksToClear as $book) {
                        if (is_array($book->authors)) {
                            $validAuthors = [];
                            foreach ($book->authors as $authorToDelete) {
                                if ($authorToDelete['_id'] !== $data_delete->_id) $validAuthors[] = $authorToDelete;
                            }
                            $book->authors = $validAuthors;
                            $book->save();
                        }
                    }

                    return $this->getHttpResponse(
                        true,
                        "Autor eliminado correctamente",
                        'ok',
                    );
                } else {
                    return $this->getHttpResponse(
                        null,
                        "No se eliminó el autor",
                        'error',
                        400
                    );
                }
            } catch (\Exception $error) {
                return $this->getHttpResponse(
                    null,
                    $error->getMessage(),
                    'error',
                    400
                );
            }
        } else {
            return $this->getHttpResponse(
                [],
                'Método no permitido',
                'error',
                405
            );
        }
    }
}
