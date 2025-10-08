# 🛠️ Laravel Product Hub — AWS SQS Integration

Este projeto é uma aplicação **Laravel** que atua como um **HUB de integração** entre mensagens recebidas de uma fila **Amazon SQS** e o processamento de atualizações de produtos em um banco de dados local.  
O objetivo é consumir mensagens, validá-las e aplicar alterações em um catálogo de produtos de forma **assíncrona, idempotente e segura**.

---

## 🚀 Arquitetura

**Fluxo principal:**

Amazon SQS → Poller (SqsListen) → Dispatch (Laravel Jobs) → Worker (ProcessProductUpdateJob) → Banco de Dados


- **Poller (SqsListen.php)**  
  Escuta indefinidamente a fila SQS e despacha mensagens recebidas como **Jobs Laravel**.

- **Worker (ProcessProductUpdateJob.php)**  
  Processa os Jobs de forma assíncrona, validando payloads, atualizando registros de produto e registrando o histórico de execução.

- **Banco de Dados**  
  - `products`: catálogo de produtos.  
  - `processed_jobs`: histórico e status de execução de jobs (Pending, Processing, Completed, Failed).

---

## 📂 Estrutura de Arquivos Importantes

- `app/Console/Commands/SqsListen.php` → Poller para SQS.  
- `app/Jobs/ProcessProductUpdateJob.php` → Worker que processa atualizações.  
- `app/Models/ProcessedJob.php` → Modelo para registrar status dos jobs.  
- `database/migrations/*` → Tabelas `products` e `processed_jobs`.  
- `config/queue.php` → Configurações de fila (SQS + database).  

---

## ⚙️ Configuração

### 1. Clonar o repositório
```bash
git clone https://github.com/alyson-monteiro/aws-sqs-job-processor.git
```
### 2. Instalar dependências
```bash
npm install
composer install
```
### 3. Configurar .env
```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=example_app
DB_USERNAME=root
DB_PASSWORD=

AWS_ACCESS_KEY_ID=SEU_KEY
AWS_SECRET_ACCESS_KEY=SEU_SECRET
AWS_DEFAULT_REGION=us-east-1
SQS_PREFIX=https://sqs.us-east-1.amazonaws.com/SEU_ID
SQS_QUEUE=irroba-product-jobs.fifo
SQS_SUFFIX=
```
4. Criar tabelas
```bash
php artisan migrate --seed
```

▶️ Execução
Rodar o Poller (escuta SQS): php artisan sqs:listen
Rodar o Worker (processa jobs internos): php artisan queue:work database --queue=product-updates --tries=1 --backoff=60 --timeout=120 -vvv

📦 Exemplo de Mensagem (AWS CLI)
```bash
aws sqs send-message \
  --queue-url "https://sqs.us-east-1.amazonaws.com/SEU_ID/irroba-product-jobs.fifo" \
  --message-group-id "product-updates" \
  --message-deduplication-id "$(uuidgen)" \
  --message-body '{
    "message_id": "test-stock-001",
    "type": "stock",
    "data": {
      "sku": "SKU-STARTER-001",
      "quantity": 99
    }
  }'
```
✅ Funcionalidades Atuais

Consome mensagens da AWS SQS (Poller).

Enfileira Jobs para processamento interno (Database Queue).

Processa payloads e aplica atualizações em produtos.

Mantém registro de cada execução em processed_jobs.

Idempotência garantida via message_id.

Logs de sucesso e falha.

📌 Requisitos da AWS

Conta AWS (Free-Tier).

Fila SQS criada e configurada.

Credenciais IAM com permissão para SQS.
