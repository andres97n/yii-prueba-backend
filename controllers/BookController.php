<?php

namespace app\controllers;

use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UnauthorizedHttpException;

use app\models\Author;
use app\models\Book;
use app\constants\GlobalConstants;
use app\helpers\TokenHelper;

/**
 * BookController implementa acciones para manejar un CRUD del modelo Libro
 *
 * @author Andrés Novillo <anovillo@libelulasoft.com>
 * @since 0.0.1
 */
class BookController extends Controller
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
     * Retorna todos los libros activos
     * @return Response
     */
    public function actionIndex(): Response
    {
        try {
            $books = Book::getBooks();
            Yii::$app->response->format = Response::FORMAT_JSON;

            return $this->getHttpResponse(
                $books,
                'Libros encontrados',
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
     * Busca un libro por su _id
     * @return Response
     */
    public function actionView(): Response
    {
        $input = file_get_contents("php://input");
        $book_response = json_decode($input);
        try {
            if (!($book_response->_id ?? null)) {
                return $this->getHttpResponse(
                    null,
                    "Se debe mandar el campo '_id'",
                    'error',
                    404
                );
            }
            if (!($book = Book::getBookById($book_response->_id))) {
                return $this->getHttpResponse(
                    null,
                    'No se encontró el libro referenciado',
                    'error',
                    404
                );
            }

            return $this->getHttpResponse(
                $book,
                'Libro encontrado',
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
     * Crea un nuevo libro y también agrega autores por sus _id's
     * @return Response
     */
    public function actionCreate(): Response
    {
        $input = file_get_contents("php://input");
        $data = json_decode($input);
        if ($this->request->isPost) {
            $book = new Book($data);
            $isValidModel = $book->validate();

            if (!$isValidModel) {
                foreach ($book->errors as $error => $value) {
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

            $book->state = GlobalConstants::STATE_ACTIVO;
            $authors = [];

            try {
                if (is_array($data->authors ?? null)) {
                    foreach ($data->authors as $authorId) {
                        $authorModel = Author::getAuthorById($authorId);
                        if (!is_null($authorModel)) {
                            unset($authorModel['books']);
                            $authors[] = $authorModel->toArray();
                        }
                    }
                }

                $book->authors = $authors;

                if ($book->save()) {
                    return $this->getHttpResponse(
                        $book,
                        'Libro agregado con éxito',
                        'ok',
                        201
                    );

                } else {
                    $errors = $book->errors;
                    return $this->getHttpResponse(
                        $errors,
                        'No se creó el Libro',
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
     * Actualiza el libro y también agrega autores por sus _id's
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
                    "Se debe enviar el '_id' del libro",
                    'error',
                    404
                );
            }

            $modelValidate = new Book($data_update);
            $isValidModel = $modelValidate->validate();

            if (!$isValidModel) {
                foreach ($modelValidate->errors as $error => $value) {
                    if ($error !== 'state') {
                        return $this->getHttpResponse(
                            null,
                            $value[0],
                            'errors',
                            404
                        );
                    }
                }
            }

            $book = Book::getBookById($data_update->_id);
            if ($book === null) {
                return $this->getHttpResponse(
                    null,
                    "No se encontró el libro referenciado",
                    'error',
                    404
                );
            }

            $book->title = $data_update->title;
            $book->publicationYear = $data_update->publicationYear;
            $book->description = $data_update->description;

            try {
                if (is_array($data_update->authors ?? null)) {
                    $authors = [];
                    foreach ($data_update->authors as $authorId) {
                        $authorModel = Author::getAuthorById($authorId);
                        if (!is_null($authorModel)) {
                            unset($authorModel['books']);
                            $authors[] = $authorModel->toArray();
                        }
                    }
                    $book->authors = $authors;
                }

                if ($book->save()) {
                    return $this->getHttpResponse(
                        $book,
                        'Libro editado con éxito',
                        'ok',
                        200
                    );
                } else {
                    return $this->getHttpResponse(
                        $book->getErrors(),
                        'No se editó el libro',
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
     * Elimina el libro y a todas sus referencias en la colección Autor
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
                    "Se debe enviar el '_id' del libro",
                    'error',
                    404
                );
            }

            try {
                $book = Book::getBookById($data_delete->_id);
                if (!($book ?? null)) {
                    return $this->getHttpResponse(
                        null,
                        "No se encontró el libro referenciado",
                        'error',
                        404
                    );
                }

                $book->state = GlobalConstants::STATE_DELETE;

                if ($book->save()) {
                    $authorsToClear = Author::find()->where([
                        'state'=>GlobalConstants::STATE_ACTIVO,
                        'books' => [
                            '$elemMatch' => ['_id' => $data_delete->_id]
                        ]
                    ])->all();

                    foreach ($authorsToClear as $author) {
                        if (is_array($author->books)) {
                            $validBooks = [];
                            foreach ($author->books as $bookToDelete) {
                                if ($bookToDelete['_id'] !== $data_delete->_id) $validBooks[] = $bookToDelete;
                            }
                            $author->books = $validBooks;
                            $author->save();
                        }
                    }

                    return $this->getHttpResponse(
                        true,
                        "Libro eliminado correctamente",
                        'ok',
                    );
                } else {
                    return $this->getHttpResponse(
                        null,
                        "No se eliminó el libro",
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