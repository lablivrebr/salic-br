# SALIC

Bem vindo/a &agrave; documenta&ccedil;&atilde;o do SALIC! Aqui voc&ecirc; vai encontrar diversas documenta&ccedil;&otilde;es sobre o processo de desenvolvimento do SALIC, versionameno e publica&ccedil;&atilde;o.

* [Esquema de desenvolvimento e banco](doc/Esquema_de_desenvolvimento_e_banco.md)
* [Guia de operação e desenvolvimento](doc/Guia_de_operacao-desenvolvimento.md)
* [Regras de versionamento](doc/Regras_versionamento.md)
* [Roteiro de publicação de releases](doc/Roteiro_de_publicacao_de_releases.md)


## Docker

Utilizamos o Docker como plataforma de desenvolvimento com o intuito de garantir o mesmo ambiente de desenvolvimento 
independentemente do Sistema Operacional(SO) utilizado. Informaçoes mais detalhadas sobre a utilização do docker clique
[aqui](doc/Guia_utilizacao_docker.md).

Para criar um ambiente para trabalhar com o SALIC basta executar o comando abaixo.

```
  docker-compose up -d
```

Para parar o container basta digitar:

```
  docker-compose stop
```
Mais informamações sobre a construção da imagem Docker utilizada nesse projeto acesse [aqui](https://github.com/culturagovbr/docker-salic-br).

## DevPage

Para informações técnicas sobre o Salic-BR clique [aqui](https://culturagovbr.github.io/salic-br)
