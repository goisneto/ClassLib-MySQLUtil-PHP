# ClassLib-MySQLUtil-PHP

Descrição
---------

Classe PHP para simplificar a criação de querys do MySQL, usando PDO, com suporte a relacionamentos de formas simplificadas.

Requisitos
----------

 - [PHP] 7.0+
 - [PDO]


Instalação
----------

 - Baixe o repositório como arquivo zip ou faça um clone;
 - Descompacte os arquivos em seu computador;
 - Execute o comando ```composer install```
 - O diretório *public* contém exemplos de chamadas utilizando a API e o diretório *source* contém a biblioteca propriamente dita.

Instalação via Composer

- Alternativamente, é possível utilizar o [Composer] para carregar a biblioteca ([goisneto/mysqlutil]).

Adicionando a dependência ao seu arquivo ```composer.json```
```composer.json
{
    "require": {
       "goisneto/mysqlutil" : "*"
    }
}
```

OU

Executando o comando para adicionar a dependência automaticamente

```php composer.phar require goisneto/mysqlutil```


Configuração
------------



Changelog
---------

0.0.0
 - Criação do Repositorio

Licença
-------

Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.


Contribuições
-------------

Achou e corrigiu um bug ou tem alguma feature em mente e deseja contribuir?

* Faça um fork
* Adicione sua feature ou correção de bug (git checkout -b my-new-feature)
* Commit suas mudanças (git commit -am 'Added some feature')
* Rode um push para o branch (git push origin my-new-feature)
* Envie um Pull Request
* Obs.: Adicione exemplos para sua nova feature. Se seu Pull Request for relacionado a uma versão específica, o Pull Request não deve ser enviado para o branch master e sim para o branch correspondente a versão.
* Obs2: Não serão aceitos PR's na branch master. Utilizar a branch de desenvolvimento.
