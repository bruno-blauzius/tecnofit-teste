# Tecnofit - Docker Setup

## Pré-requisitos
- Docker
- Docker Compose

## Instalação e Execução

### 1. Iniciar os containers
```bash
docker-compose up -d
```

### 2. Instalar Laravel (primeira vez)
```bash
docker-compose exec php-apache composer create-project laravel/laravel . --prefer-dist
```

### 3. Configurar permissões
```bash
docker-compose exec php-apache chmod -R 777 storage
docker-compose exec php-apache chmod -R 777 bootstrap/cache
```

### 4. Gerar chave da aplicação
```bash
docker-compose exec php-apache php artisan key:generate
```

### 5. Acessar a aplicação
- **URL**: http://localhost:8000
- **Banco de dados**: localhost:3306
- **Usuário BD**: tecnofit
- **Senha BD**: tecnofit123

## Comandos Úteis

### Ver logs
```bash
docker-compose logs -f php-apache
```

### Parar containers
```bash
docker-compose down
```

### Reiniciar containers
```bash
docker-compose restart
```

### Executar Artisan commands
```bash
docker-compose exec php-apache php artisan [comando]
```

### Executar Composer
```bash
docker-compose exec php-apache composer [comando]
```

## Estrutura de Volumes

- `./app` → `/var/www/html` (seu código Laravel)
- `db-data` → Armazena dados do MySQL

## Configuração Automática

O Dockerfile já tem:
- PHP 8.3 com Apache
- Composer atualizado
- Extensões: PDO, MySQL, GD, ZIP
- mod_rewrite habilitado para Laravel

## Notas

- O volume `./app` permite desenvolvimento sem rebuild
- O banco de dados persiste com o volume `db-data`
- Todos os containers estão conectados via rede `tecnofit-network`

