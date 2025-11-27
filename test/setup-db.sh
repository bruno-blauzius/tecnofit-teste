#!/bin/bash
# Setup do Banco de Dados de Testes
# Execute: bash test/setup-db.sh

echo "🚀 Configurando banco de dados de testes..."

# Verifica se o container db-test está rodando
if ! docker compose ps db-test | grep -q "Up"; then
    echo "⚠️  Container db-test não está rodando. Iniciando..."
    docker compose up -d db-test
    echo "⏳ Aguardando container inicializar..."
    sleep 5
fi

# Recria o banco de dados
echo "📦 Recriando database hyperf_test..."
docker compose exec db-test mysql -u root -proot -e "DROP DATABASE IF EXISTS hyperf_test; CREATE DATABASE hyperf_test;" 2>/dev/null

# Cria as tabelas
echo "📊 Criando tabelas..."
docker compose exec -T db-test mysql -u root -proot hyperf_test < test/schema.sql

# Verifica se as tabelas foram criadas
echo "✅ Verificando tabelas criadas..."
docker compose exec db-test mysql -u root -proot hyperf_test -e "SHOW TABLES;"

echo ""
echo "✨ Setup completo! Agora você pode executar os testes:"
echo "   docker compose exec application composer test"
