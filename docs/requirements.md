# Requisitos Funcionais — MVP CRM com IA e WhatsApp

## 1. Objetivo

Desenvolver um CRM web com integração WhatsApp e agente de IA para gerenciamento de atendimentos comerciais através de funil Kanban.

O sistema deve permitir:

* recebimento de mensagens WhatsApp
* criação automática de atendimentos
* interação automática via IA
* gerenciamento visual de leads
* acompanhamento do histórico de conversas

---

# 2. Escopo do MVP

O MVP deve contemplar exclusivamente:

* integração WhatsApp
* gestão de leads
* histórico de conversa
* Kanban
* atendimento automático via IA
* movimentação automática de estágio
* autenticação de usuários

---

# 3. Requisitos Funcionais

## 3.1 Autenticação

### RF-001 — Login

O sistema deve permitir autenticação utilizando:

* email
* senha

### RF-002 — Sessão autenticada

O sistema deve restringir acesso às funcionalidades apenas para usuários autenticados.

---

# 3.2 Gestão de Leads

### RF-003 — Criação automática de lead

Ao receber uma nova mensagem WhatsApp de um número ainda não cadastrado:

* o sistema deve criar automaticamente um lead
* o sistema deve iniciar uma conversa vinculada ao lead

### RF-004 — Dados do lead

O lead deve possuir:

* nome
* telefone
* email (opcional)
* empresa (opcional)
* observações internas
* data de criação

### RF-005 — Atualização de lead

Usuários autenticados devem conseguir editar os dados do lead.

---

# 3.3 Conversas

### RF-006 — Registro de mensagens

O sistema deve registrar todas as mensagens:

* recebidas do WhatsApp
* enviadas pela IA
* enviadas manualmente por usuários

### RF-007 — Histórico da conversa

O sistema deve exibir o histórico completo da conversa em ordem cronológica.

### RF-008 — Origem da mensagem

Cada mensagem deve possuir identificação de origem:

* cliente
* IA
* usuário interno

### RF-009 — Data e hora

Cada mensagem deve possuir:

* data
* horário de envio

---

# 3.4 Integração WhatsApp

### RF-010 — Recebimento de mensagens

O sistema deve receber mensagens através de webhook da API WhatsApp.

### RF-011 — Envio de mensagens

O sistema deve conseguir enviar mensagens WhatsApp através da API integrada.

### RF-012 — Associação de conversa

Mensagens recebidas devem ser vinculadas automaticamente ao lead correto utilizando o número de telefone.

---

# 3.5 Kanban

### RF-013 — Visualização Kanban

O sistema deve possuir visualização Kanban para gerenciamento dos leads.

### RF-014 — Estágios padrão

O MVP deve possuir os seguintes estágios:

* Novo Lead
* Qualificação
* Proposta
* Fechado

### RF-015 — Card do lead

Cada lead deve ser exibido como um card dentro do Kanban.

### RF-016 — Movimentação manual

Usuários devem conseguir mover cards manualmente entre colunas.

### RF-017 — Movimentação automática

O sistema deve permitir que a IA altere automaticamente o estágio do lead.

---

# 3.6 Atendimento com IA

### RF-018 — Resposta automática

O sistema deve responder automaticamente mensagens recebidas utilizando IA.

### RF-019 — Contexto da conversa

A IA deve receber:

* histórico recente da conversa
* resumo da conversa
* estágio atual do lead

### RF-020 — Prompt de sistema

O sistema deve utilizar um prompt fixo para definição do comportamento do agente.

### RF-021 — Resposta estruturada

A IA deve retornar:

* mensagem de resposta
* estágio sugerido do lead
* resumo atualizado da conversa

### RF-022 — Atualização automática de estágio

Quando a IA retornar um novo estágio:

* o sistema deve mover automaticamente o lead no Kanban

### RF-023 — Atualização do resumo

Após cada interação da IA:

* o sistema deve atualizar o resumo da conversa

---

# 3.7 Resumo de Conversa

### RF-024 — Resumo persistido

Cada conversa deve possuir um resumo textual persistido.

### RF-025 — Atualização incremental

O resumo deve ser atualizado continuamente conforme novas mensagens forem processadas.

---

# 3.8 Painel de Atendimento

### RF-026 — Lista de conversas

O sistema deve exibir lista de conversas ativas.

### RF-027 — Conversa em tempo real

Novas mensagens devem aparecer automaticamente na interface sem necessidade de atualização manual da página.

### RF-028 — Envio manual de mensagens

Usuários autenticados devem conseguir enviar mensagens manualmente ao cliente.

---

# 3.9 Integração com IA

### RF-029 — API compatível OpenAI

O sistema deve consumir API compatível com OpenAI para inferência do modelo.

### RF-030 — Configuração da API

O sistema deve permitir configuração de:

* endpoint da API
* chave da API
* modelo utilizado

### RF-031 — Persistência do contexto

O backend deve reconstruir o contexto da conversa a cada nova interação utilizando:

* resumo
* últimas mensagens

---

# 4. Regras de Negócio

## RN-001

Um lead deve possuir apenas uma conversa ativa por vez.

## RN-002

Toda mensagem recebida deve ser persistida antes do processamento da IA.

## RN-003

A IA nunca deve enviar mensagens diretamente ao WhatsApp sem passar pelo backend.

## RN-004

Toda resposta gerada pela IA deve ser persistida no histórico antes do envio.

## RN-005

A movimentação automática do Kanban deve ocorrer apenas após resposta válida da IA.

## RN-006

Mensagens manuais enviadas por usuários internos também devem compor o contexto enviado à IA.

---

# 5. Requisitos Não Funcionais

## RNF-001 — Persistência

Todos os dados devem ser persistidos em banco relacional.

## RNF-002 — Tempo real

As conversas devem atualizar em tempo real na interface web.

## RNF-003 — Segurança

A API de IA deve utilizar autenticação por chave de API.

## RNF-004 — Logs

O sistema deve registrar erros de integração:

* WhatsApp
* IA
* envio de mensagens

## RNF-005 — Escalabilidade básica

O sistema deve suportar múltiplas conversas simultâneas sem perda de mensagens.

---

# 6. Integrações Externas

## WhatsApp

Integração via API compatível com:

* envio de mensagens
* recebimento via webhook

## IA

Integração via endpoint compatível com OpenAI Chat Completions API.

---

# 7. Fluxo Principal

## Fluxo de atendimento

1. Cliente envia mensagem no WhatsApp
2. Sistema recebe webhook
3. Sistema identifica ou cria lead
4. Sistema salva mensagem
5. Sistema recupera contexto da conversa
6. Sistema envia contexto para IA
7. IA retorna:

   * resposta
   * estágio
   * resumo
8. Sistema salva resposta
9. Sistema atualiza Kanban
10. Sistema envia mensagem ao cliente
