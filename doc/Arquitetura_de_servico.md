# Arquitetura de Serviço

Esta é uma proposta de arquitetura de serviço com vistas a oferecer o SALIC-BR como serviço (Software as a Service - SaaS). 

O SALIC-BR é um porte do sistema Salic MinC com adaptações para funcionar com o PostgreSQL, eliminando o risco relacionado à dependência de fornecedor.

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

Este piloto foi construído usando ZendFramework 1.12 e PHP 5.6 por se tratar de versões bem estabelecidas
e conhecidas, visando assim a estabilidade do sistema. Já vislumbramos, no entanto,  a necessidade de migração
para versões posteriores.

## Como utilizar o SALIC-BR?

Para obter a aplicação funcionando basta um comando na raiz do projeto, após o que a aplicação passa a estar disponível em `http://localhost`:
```
    docker-compose up -d
```

Ajustes, se necessários, podem ser realizados no arquivo ```docker-compose.yml``` localizado na raiz do projeto. Por exemplo:
- Mapeamento de portas para redirecionamento;
- Mapeamento de volumes para compartilhamento de diretórios.

# Proposta de oferta de SaaS

Existem diversas formas de oferecer um software enquanto serviço. Devido as particularidades do SALIC-MinC uma maneira encontrada foi utilizar containers que permitem criar diferentes instâncias isoladas dentro de uma mesma rede.

Para alcançar o cenário proposto utilizamos a plataforma Docker dividindo em dois containers:
- Banco e dados, contendo apenas uma instancia do SGBD PostgreSQL;
- Aplicação, contendo todo o resto.

São necessários portanto no mínimo dois containers (Aplicação e Banco de Dados) para executar uma instância do SALIC-BR.
