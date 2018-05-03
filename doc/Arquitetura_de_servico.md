# Arquitetura de Serviço

Esta é uma proposta de arquitetura de serviço com vistas a oferecer o SALIC-BR como serviço(Software as a Service - SaaS). 

O SALIC-BR é um porte do sistema Salic MinC com adaptações para funcionar com o PostgreSQL.

## Funcionalidades Disponíveis

Atualmente o SALIC-BR implementa as seguintes funcionalidades:
- Autenticação
  - Cadastro de Usuário
  - Recuperação de senha
  - Suporte a autenticação utilizando o ID Cultura (https://id.cultura.gov.br/)
- Proponente
  - Cadastro de Proponente
  - Gerenciamento de Responsáveis
- Proposta Cultural
  - Listar Proposta Cultural
  - Cadastro:
    - Proposta
    - Local de realização / Deslocamento
    - Plano de distribuição
    - Planilha Orçamentária
    - Itens Orçamentários
    - Diligências
  - Exclusão de Proposta Cultural
  - Envio de Proposta Cultural para Análise

## Tecnologias

Com o objetivo de facilitar a utilização do SALIC-BR, foram utilizadas as seguintes tecnologias:
- Docker (https://www.docker.com/)
- Docker Compose (https://docs.docker.com/compose/)
- Apache 2.4
- PHP 5.6
- Composer (https://getcomposer.org/)
- ZendFramework 1.12 (https://framework.zend.com/manual/1.12/en/manual.html)
- Banco de dados PostgreSQL (https://www.postgresql.org/)

## Como utilizar o SALIC-BR?

Para obter a aplicação funcionando basta executar o comando abaixo na raiz deste projeto clonado:
```
    docker-compose up -d
```

Caso tenha o interesse alguns ajustes podem ser realizados no arquivo ```docker-compose.yml``` localizado na raiz do projeto, como por exemplo:
- Mapeamento de portas para redirecionamento
- Mapeamento de volumes para compartilhamento de diretórios.

```
    Acessar: http://localhost
```

# Proposta de oferta de SaaS

Existem diversas formas de oferecer um software enquanto serviço. Devido as particularidades do SALIC-Minc uma maneira encontrada foi utilizar containers que permitem criar diferentes instâncias isoladas dentro de uma mesma rede.

Para alcançar o cenário proposto utilizamos a plataforma Docker dividindo em dois containers:
- Aplicação
- Banco e dados

Sendo necessário no mínimo dois containers(Aplicação e Banco de Dados) para executar uma instância do SALIC-BR.
