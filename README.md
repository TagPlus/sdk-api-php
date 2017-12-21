# SDK PHP TagPlus

A SDK facilita a integração com a nossa API do ERP, principalmente nas etapas de autenticação.

## Requisitos

* PHP >= 5.4
* [Composer](https://getcomposer.org)

## Preparando

Vamos instalar a SDK via Composer:

```bash
composer require tagplus/sdk-api-php
```

## Como usar

**IMPORTANTE:** Para prosseguir você já precisa ter se registrado no portal da API e ter disponível seu `client_id`, `client_secret` e `redirect_uri`. Caso ainda não tenha se registrado acesse [aqui](https://apidoc.tagplus.com.br/) e clique em Cadastrar.

A API utiliza o OAuth2 para autenticação, vamos mostrar um exemplo de como você deve utilizar a SDK para efetuar a autenticação. Confira nossa documentação de API [aqui](https://apidoc.tagplus.com.br/doc/).

### 1. Autorização do usuário

O primeiro passo é redirecionar o usuário para o TagPlus, para que assim ele possa autorizar sua aplicação. Normalmente é um link em determinado pedaço da sua página web.

```php
<?php

// Mude o diretório de acordo com sua estrutura de pasta
require_once __DIR__ . '/vendor/autoload.php'; 

use Tagplus\Client;

$config = [
    'client_id' => 'xxx',
    'client_secret' => 'yyy',
    'scope' => [
        'read:financeiros'
    ]
];

$authUrl = Client::getAuthorizationUrl(
    $config['client_id'],
    $config['scope']
);
?>

<a href="<?=$authUrl?>">Conectar ao TagPlus</a>
```

Ao clicar no link o usuário poderá autorizar (ou não) a sua aplicação a acessar os recursos solicitados (scope).

### 2. Receber o código e recuperar token

Como término do passo anterior o usuário será redirecionado para a url cadastrada no portal (`redirect_uri`).

Nesse página de retorno (*callback*) você deve ter os seguintes trechos de código:

```php
<?php

// Mude o diretório de acordo com sua estrutura de pasta
require_once __DIR__ . '/vendor/autoload.php'; 

use Tagplus\Client;
use kamermans\OAuth2\Persistence\FileTokenPersistence;

// Mude a localização da pasta conforme necessário
$tokenPersistence = new FileTokenPersistence(__DIR__ . '/clienteA.txt');

$config = [
    'client_id' => 'xxx',
    'client_secret' => 'yyy',
    'scope' => [
        'read:financeiros'
    ]
];

Client::getAccessToken(
    $config, 
    $tokenPersistence
);

// Neste ponto você pode redirecionar o usuário para outra página 

```

O trecho de código acima vai recuperar um *access token* válido e salvá-lo no arquivo cujo nome foi passado ao criar a classe *FileTokenPersistence*.

### 3. Acessando API

Agora já está tudo pronto para utilizar a API:

```php
<?php

// Mude o diretório de acordo com sua estrutura de pasta
require_once __DIR__ . '/vendor/autoload.php'; 

use Tagplus\Client;
use kamermans\OAuth2\Persistence\FileTokenPersistence;

$config = [
    'client_id' => 'xxx',
    'client_secret' => 'yyy',
    'scope' => [
        'read:financeiros'
    ]
];

// Mude a localização da pasta conforme necessário
$tokenPersistence = new FileTokenPersistence(__DIR__ . '/clienteA.txt');

$api = new Client(
    $config,
    [],
    $tokenPersistence
);

$response = $api->get('/me');

$me = json_decode($response->getBody());

// Imprimindo o nome do usuário que o token está vinculado
echo 'Nome do usuário: ' $me->nome;

```

Esse foi apenas um exemplo de como usar a API do ERP TagPlus.
Para mais detalhes de quais recursos estão disponíveis acesse nossa [referência](https://apidoc.tagplus.com.br/doc).

## Próximos passos

[ ] Explicar como armazenar os tokens no BD (ex.: MySQL);
[ ] Explicar como usar a SDK junto com uma framework (ex.: Laravel, CodeIgniter, Symfony, etc...);
[ ] Recuperar todas as páginas (quando houver) automaticamente;
[ ] Já retornar a instância do Objeto, sem precisar chamar a função json_decode;
[ ] Ter métodos para todos os recursos, ao invés de passar a URL como parâmetro (ex.: `$api->getClientes()`).
