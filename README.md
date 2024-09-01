
Proyecto Backend Libélula

El siguiente proyecto es una prueba de aprobación de conocimientos de backend usando el framework Yii2 con mongodb 


ESTRUCTURA DE DIRECTORIOS
-------------------

      commands/           Contiene archivos que se usan para ejecutar acciones desde la línea de comandos
      config/             Archivos de configuración
      controllers/        Contiene las acciones a realizar con los modelos
      models/             Contiene los modelos de las base de datos
      helpers/            Funciones reutilizables a lo largo del código
      tests/              Contiene archivos para realizar pruebas del código
      web/                Contiene los archivos de configuración y despliegue del proyecto



REQUERIMIENTOS 
------------

- El requerimiento es tener instalado PHP 7.4 o superior; tener instalado ya sea Wampp o Xampp, o un compilador de php.
- Tener el paquete de composer para instalar paquetes de PHP.
- Instalar la extensión de yii/mongodb para realizar la conexión a la base de datos.
- Se debe tener instalado MongoDB como sistema gestor de base datos.


INSTALACIÓN
------------

### Install via Composer

Para crear el proyecto ejecutar el siguiente comando

~~~
composer create-project --prefer-dist yiisoft/yii2-app-basic nombre-proyecto
~~~

Entrar a la carpeta del proyecto e instalar el complemento de yii con mongodb

~~~
composer require yiisoft/yii2-mongodb
~~~

CONFIGURACIÓN
-------------

### Database

- Edita el archivo `config/db.php` con la conexión a tu base de datos local:

```php
return [
    'class' => 'yii\mongodb\Connection',
    'dsn' => 'mongodb://localhost:27017/nombre-base-datos',
];
```

Antes de iniciar el proyecto se debe tener ya creada la base de datos en tu entorno local 

- Añade también la conexión en el archivo `config/web.php` con la conexión a tu base de datos local:

```php
return [
    'mongodb' => [
            'class' => 'yii\mongodb\Connection',
            'dsn' => 'mongodb://localhost:27017/nombre-base-datos',
        ],
];
```

- Y descomenta las siguientes líneas de código:
```php
'urlManager' => [
   'enablePrettyUrl' => true,
   'showScriptName' => false,
   'rules' => [
   ],
],
```
Esto nos ayuda para que las url's de las api's sean más fáciles de manejar y entendibles para el consumidor.

- Para ejecutar el proyecto y estás usando ya se Xampp o Wampp simplemente enciende los servicios de PHP y escribe la dirección de tu proyecto
Ejemplo: http://localhost/nombre-proyecto/web/

De esta manera el proyecto estará ejecutándose en tu servidor local y conectado a tu base de datos; y ya podrás crear tus modelos y servicios para ejecutarlos desde cualquier parte.

