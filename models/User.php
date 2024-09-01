<?php

namespace app\models;

use Yii;
use app\constants\GlobalConstants;
use yii\mongodb\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * Este modelo hace refencia a la colección 'users'
 * @property string $username
 * @property string $password_hash
 * @property string|null $auth_key
 * @property int|null $auth_key_expiration
 *
 * @author Andrés Novillo <anovillo@libelulasoft.com>
 * @since 0.0.1
 */

class User extends ActiveRecord implements IdentityInterface
{
    /**
     * Nombres de base de datos y colección
     * @return array
     */
    public static function collectionName(): array
    {
        return ['library', 'users'];
    }

    /**
     * Lista de atributos
     * @return array
     */
    public function attributes()
    {
        return ['_id', 'username', 'password_hash', 'auth_key','auth_key_expiration'];
    }

    /**
     * Encuentra un usuario por su _id
     * @param string $id
     * @return User|null
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * Encuentra un usuario por el auth_token
     * @param string $token
     * @param mixed $type
     * @return User|null
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        $user = self::findOne(['auth_key' => $token]);
        if ($user && $user->isTokenValid()) {
            return $user;
        }
        return null;
    }

    /**
     * Encuentra un usuario por el username
     * @param string $username
     * @return static|null
     */
    public static function findByUsername(string $username)
    {
        return self::findOne(['username' => $username]);
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        return $this->authKey;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return $this->authKey === $authKey;
    }

    /**
     * Valida que la contraseña sea la misma del usuario actual
     * @param string $password password to validate
     * @return bool
     */
    public function validatePassword(string $password): bool
    {
        return $this->password_hash === $password;
    }

    /**
     * Genera una nueva clave de autenticación (auth_key) y establece su expiración.
     * @return void
     */
    public function generateAuthKey(): void
    {
        $this->auth_key = Yii::$app->getSecurity()->generateRandomString();
        $this->auth_key_expiration = time() + GlobalConstants::TOKEN_EXPIRATION;
        $this->save(false);
    }

    /**
     * Retorna si el token es válido o no
     * @return bool
     */
    public function isTokenValid(): bool
    {
        return $this->auth_key_expiration > time();
    }
}
