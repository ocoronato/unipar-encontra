# UNIPAR ENCONTRA

**Sistema web de Achados e Perdidos** da UNIPAR — Projeto Integrador do curso de Sistemas de Informação.

Alunos e funcionários cadastram objetos **perdidos** ou **encontrados**, pesquisam os objetos publicados e,
ao reconhecer um objeto encontrado, pedem a devolução ("Este objeto é meu"). A administração modera as
publicações, analisa as solicitações e registra as devoluções.

## Tecnologias

| | Versão |
|---|---|
| PHP | 8.4 |
| Laravel | 12 |
| Livewire + Volt | 4 / 1 |
| Flux UI (componentes gratuitos) | 2 |
| Tailwind CSS + Vite | 4 / 6 |
| Banco de dados | MySQL 8 (testes automatizados usam SQLite em memória) |

Não há framework JavaScript (React/Vue): as telas são componentes Livewire (arquivos Volt em
`resources/views/livewire`).

## Funcionalidades

**Usuário**
- Cadastro, login, perfil (RA e curso opcionais) e troca de senha.
- Cadastrar objeto perdido/encontrado com até 5 fotos.
- Buscar objetos com filtros (texto, tipo, categoria, local, período, status) e paginação.
- Ver detalhes com galeria e solicitar a devolução de um objeto encontrado.
- **Meus Objetos**: visualizar, editar e cancelar publicações.
- **Minhas Solicitações**: acompanhar e cancelar pedidos de devolução.

**Administração** (`/admin`)
- Dashboard com indicadores e gráfico mensal.
- Cadastros: usuários (promover/rebaixar administrador), categorias e locais.
- Movimentos: moderação de objetos, análise de solicitações e histórico de devoluções.
- Relatórios com filtros e versão para impressão: objetos perdidos, encontrados, devolvidos e solicitações.

## Regras de negócio principais

```
Objeto:        cadastrado (aguardando aprovação) → aprovado (aparece na busca) ou rejeitado (com motivo)
               editado pelo autor → volta para "aguardando aprovação"

Devolução:     solicitação pendente → aprovada (objeto "em processo de devolução")
                                    → devolução confirmada (objeto "devolvido", registro em item_returns)
               a solicitação também pode ser rejeitada (com resposta) ou cancelada pelo solicitante
```

- Só objetos **encontrados, aprovados e disponíveis** aceitam solicitações de devolução.
- Não é permitido: pedir o próprio objeto, pedir duas vezes, pedir de novo após uma rejeição,
  pedir objeto em devolução ou já devolvido.
- Ao confirmar a devolução, as outras solicitações pendentes do objeto são rejeitadas automaticamente.
- Nada do histórico é apagado: categorias/locais em uso não podem ser excluídos (apenas desativados) e
  usuários com publicações ou solicitações não podem excluir a própria conta.

## Instalação (desenvolvimento)

Pré-requisitos: PHP 8.2+ (extensões `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `exif`, `zip`, `intl`),
Composer, Node.js 20+ e MySQL 8.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configure o banco no `.env` (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`), crie o banco
`unipar_encontra` no MySQL e rode:

```bash
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

Acesse http://127.0.0.1:8000. Durante o desenvolvimento, `npm run dev` recompila o CSS automaticamente.

### Contas de desenvolvimento

Criadas pelos seeders **apenas fora de produção** (senhas de teste, não use em produção):

| Perfil | E-mail | Senha |
|---|---|---|
| Administrador | `admin@unipar-encontra.test` | `password` |
| Aluno | `aluno@unipar-encontra.test` | `password` |

O seeder também cria categorias, locais e objetos/solicitações de exemplo.
`php artisan migrate:fresh --seed` recria o banco do zero (**apaga todos os dados**).

## Testes

```bash
php artisan test
```

Cobrem autenticação, autorização administrativa, cadastro de objetos e upload, busca, detalhes,
solicitação de devolução, moderação, fluxo de devolução, cadastros, dashboard e relatórios.

Padrão de código (PSR/Laravel): `vendor/bin/pint`.

## Onde está cada coisa

| Pasta/arquivo | Conteúdo |
|---|---|
| `app/Models` | Models e relacionamentos (`LostFoundItem` = objeto; "Object" é palavra reservada no PHP) |
| `app/Enums` | Tipos e status, com rótulo em português e cor dos badges |
| `app/Policies` | Regras de permissão (quem pode ver, editar, pedir devolução, moderar...) |
| `app/Actions/StoreItemPhotos.php` | Salvamento das fotos (remove EXIF/GPS e redimensiona) |
| `app/Livewire/Concerns/WithReportFilters.php` | Recursos comuns dos relatórios (período e impressão) |
| `resources/views/livewire` | Telas (componentes Livewire/Volt) |
| `resources/views/components` | Componentes Blade reutilizáveis (card de objeto, badges, layouts) |
| `routes/web.php` | Rotas (área admin protegida pelo Gate `access-admin`) |
| `database/migrations`, `database/seeders` | Estrutura do banco e dados iniciais |
| `lang/pt_BR` | Traduções (validação, autenticação...) |
| `tests/Feature`, `tests/Unit` | Testes automatizados |

## Segurança e privacidade

- Área administrativa protegida no backend (middleware `can:access-admin`, aplicado também às ações
  Livewire) e regras por usuário nas Policies — não depende de esconder botões.
- Proteção contra *mass assignment*: `role`, `status`, `approval_status` etc. não vêm de formulários.
- Propriedades Livewire sensíveis marcadas com `#[Locked]`.
- Uploads: somente JPG/PNG/WEBP, até 5 MB e 5 fotos; uploads temporários ficam em disco privado;
  as fotos são recriadas sem metadados (a localização GPS da foto é removida).
- Dados pessoais (nome, e-mail, RA) de quem publica nunca aparecem para outros usuários;
  publicações não aprovadas respondem 404 para terceiros.
- CSRF, validação e escape de HTML são os padrões do Laravel/Livewire.

## Publicação em produção (checklist)

- `APP_ENV=production`, `APP_DEBUG=false` e `APP_URL` com o endereço real (HTTPS).
- `SESSION_SECURE_COOKIE=true` quando usar HTTPS.
- Usuário e senha próprios no MySQL (não usar `root` sem senha).
- `composer install --no-dev --optimize-autoloader`, `npm run build`, `php artisan migrate --force`,
  `php artisan db:seed --force` (cria as categorias e locais iniciais), `php artisan storage:link` e
  `php artisan optimize`.
- Criar o primeiro administrador manualmente (os seeders de exemplo não rodam em produção) e
  promovê-lo, por exemplo:
  `php artisan tinker --execute="App\Models\User::where('email', 'seu@email')->first()->forceFill(['role' => 'admin'])->save();"`
