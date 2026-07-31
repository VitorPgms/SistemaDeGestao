# Decisões Arquiteturais

## Produtos

Produtos são globais.

O estoque define em quais CDs eles existem.

---

## Categorias

Categorias são globais.

---

## Fornecedores

Cada CD possui seus próprios fornecedores.

---

## Estoque

Controlado por CD.

---

## Dashboard

Utilizar Blade.

Utilizar Chart.js.

---

## Filament

Usado apenas para cadastros administrativos.

---

## Blade

Usado para todas as operações do sistema.

---

## Navegação

Cadastros, Operações e BI compartilham o mesmo shell do Filament — mesmo menu lateral, cabeçalho e navegação. O usuário não deve perceber fronteira entre eles.

Motivo: a separação original (Filament para Cadastros, Blade+Controller isolado para Operações) dava a sensação de dois sistemas diferentes ao alternar entre eles, mesmo depois de replicar visualmente o menu — a primeira tentativa (sidebar Blade própria + `wire:navigate`) ainda mantinha dois shells paralelos para manter sincronizados.

Implementação: a exibição (GET) de cada tela de Operações/BI passa a ser uma Filament Page fina — reaproveita o shell real do Filament em vez de uma cópia mantida à mão. A Page só devolve a mesma view Blade de sempre (`getViewData()` monta o mesmo array que o Controller já montava), sem Forms/Tables do Filament. Toda escrita (criar, editar, cancelar) continua batendo nos Controllers, Requests e Services atuais via POST/PUT comum — nenhuma regra de negócio passa a viver em componente Livewire.

Revisa a decisão anterior de navegação (wire:navigate sobre layout Blade próprio): o layout Blade próprio (`app-layout`/`app-sidebar`) é substituído pelo shell nativo do Filament, eliminando a duplicação de manter dois menus visualmente idênticos.

Consequência aceita: rotas de exibição (`entradas.index`, `saidas.index`, `estoque.index`, `estoque.edit`, `inventarios.index/create/show`, `dashboard.index`) passam a ter nomes gerados pelo Filament em vez dos nomes atuais — Controllers (redirects) e testes que referenciam esses nomes precisam ser atualizados. Rotas de escrita (`entradas.store`, `saidas.store`, `estoque.update`, `inventarios.store/contagem/finalizar/cancelar`, `dashboard.notificacoes.marcar-lida`) não mudam.

---

## Segurança

Usuário comum enxerga apenas seu CD.

Administrador pode visualizar todos os CDs.

---

## Colaboradores

Status e data de demissão são consistentes entre si: colaborador inativo exige data de demissão; colaborador ativo não pode possuir data de demissão. Validado no formulário Filament (`requiredIf`/`prohibitedUnless`) e reforçado no Model via `saving` (defesa em profundidade, mesmo padrão já usado em `BelongsToCd`).

Setor vinculado a um colaborador deve pertencer ao mesmo CD do colaborador. Validado no formulário Filament (`exists` com `cd_id`) e reforçado no Model.

Um Setor não pode ser desativado enquanto possuir colaboradores ativos vinculados.

---

## Entradas — UX

Filtros em dois níveis: rápidos (Produto, Período, Nº da NF) sempre visíveis; avançados (Categoria, Fornecedor, Responsável pelo Recebimento, Status, CD) atrás de um botão "Mais filtros", usando Alpine.js só para mostrar/esconder (nenhuma regra de negócio no JS). O painel avançado abre sozinho quando a página recarrega com algum desses filtros preenchidos, para o usuário não perder de vista um filtro já aplicado.

Cancelamento de Entrada passou a ter confirmação em duas etapas (motivo obrigatório → confirmação final) via um modal Alpine.js, no lugar do `confirm()` do navegador. Continua sendo o mesmo POST/Controller/Service de sempre — só a camada de confirmação no cliente mudou.

Notificações de sucesso/erro do módulo passaram a usar `Filament\Notifications\Notification::make()->send()` a partir do Controller (Blade comum, sem Livewire) em vez do banner `session('sucesso')/session('erro')`. Isso funciona porque o componente `Filament\Livewire\Notifications` já vem embutido no layout base do painel — só reaproveita o listener que já existe, sem infraestrutura nova. Os demais módulos continuam no banner `<x-alert>`/`session('sucesso')` até decidirmos migrar.

Motivo: pedido explícito de UX só para Entradas — não é para replicar nos outros módulos ainda. Revisar quando os próximos módulos (Saídas, Estoque) forem retrabalhados, para decidir se esse padrão vira o padrão geral do sistema.

**Atualização (Módulo 9 — Saídas):** o padrão foi estendido para Saídas (status Ativa/Cancelada, cancelamento em duas etapas com motivo obrigatório, filtros avançados atrás de "Mais filtros", notificações via `Filament\Notifications\Notification` em editar/cancelar). Fluxo de criação (`SaidaController::store`) continua no banner `session('sucesso')` original — não foi alterado. Passa a ser o padrão comum das duas telas de Operações já implementadas; Estoque ainda não foi retrabalhado.

**Importante — build do front-end:** este ambiente não tem Node/npm instalado, e `public/build/` é um bundle Tailwind pré-compilado que não é regerado automaticamente. Qualquer classe Tailwind usada pela primeira vez em uma tela nova (que ainda não apareça em nenhum outro Blade já existente) não vai existir no CSS compilado até alguém rodar `npm run build` numa máquina com Node. O modal de cancelamento deste módulo foi escrito com um pequeno bloco `<style>` próprio (classes `cancelamento-modal-*`), em vez de utilitários Tailwind, exatamente para não depender desse rebuild. Ao criar novas telas com elementos visuais que não existem em nenhuma tela atual (modais, overlays, etc.), usar o mesmo caminho — CSS escrito à mão — ou confirmar antes que o build será atualizado.

---

## Estoque — Correção: edição de Entrada bloqueada indevidamente

`EstoqueService::atualizarEntrada()` revertia sempre a quantidade **antiga** inteira da Entrada antes de aplicar a nova. Como a reversão usa o estoque agregado do produto/CD (não um saldo por Entrada), qualquer Saída que já tivesse consumido estoque suficiente para deixar o total abaixo da quantidade antiga bloqueava **qualquer** edição dessa Entrada — mesmo aumentando a quantidade, ou só alterando um campo não relacionado (ex: anexar a Nota Fiscal), mesmo quando a edição era perfeitamente válida.

Corrigido para aplicar apenas a diferença (quantidade nova − antiga) quando produto/variação não mudam, bloqueando somente quando essa diferença não cabe no saldo atual. Quando produto/variação mudam, mantém o comportamento anterior (reverte tudo do produto antigo, aplica tudo no produto novo).

Motivo: bug relatado pelo usuário ao tentar editar uma Entrada após uma Saída ter consumido parte do estoque. Testes de regressão em `EstoqueServiceTest`.

---

## PGR

Ainda não implementado. Quando for implementado, o PGR será vinculado ao Centro de Distribuição, e não ao colaborador.

Nenhuma migration ou código para PGR foi criado até o momento — esta seção registra apenas a decisão de vínculo para quando a funcionalidade for de fato implementada.

---

## Colaborador — Histórico

O histórico individual do colaborador não vincula Entradas (o modelo de Responsável pelo Recebimento das Entradas não foi alterado). Ele mostra:

- Saídas de produtos vinculadas ao colaborador (`Colaborador::saidas()`), somente leitura;
- Alterações cadastrais registradas pelo Activity Log (`Colaborador::activities()`, já fornecido pelo `LogsActivity`), somente leitura.

Implementado como dois RelationManagers (`SaidasRelationManager`, `AtividadesRelationManager`) na tela de edição do `ColaboradorResource`, sem tela nova nem módulo novo.

---

## Colaborador — Exame Periódico

Aviso de exame periódico é calculado sob demanda a partir de `data_proximo_exame_periodico` (sem Scheduler/Cron/Job): `Colaborador::statusExamePeriodico()` retorna `vencido`, `proximo` (dentro de `Colaborador::DIAS_ALERTA_EXAME_PERIODICO`, 30 dias) ou `normal`, exibido como badge colorido na listagem. Não há notificação persistente para isso.

---

## Documentos

Não existe módulo de Documentos. Arquivos ficam vinculados ao registro correspondente via o Media Library já usado pelo Produto (`spatie/laravel-medialibrary`):

- Colaborador: coleção `documentos` (múltiplos arquivos, PDF/JPEG/PNG), anexável no próprio cadastro;
- Entrada: coleção `nota_fiscal` (arquivo único, PDF/JPEG/PNG), anexável no formulário de Entrada. O anexo permanece vinculado à Entrada mesmo após edição ou cancelamento — só é substituído quando um novo arquivo é enviado.

---

## Estoque — Quanto foi comprado

Nenhuma tabela nova. `EstoqueService::anexarTotaisDoPeriodo()` soma `quantidade` de Entradas e Saídas com status Ativa dentro do período informado, excluindo registros originados por ajuste de inventário (`origem_type` não nulo — preenchido só pelo fluxo de ajuste em `EstoqueService::registrarAjusteInventario`). Exibido na listagem de Estoque (`EstoqueLista`) junto com um filtro de período (padrão: mês atual).

---

## Filosofia

Sempre escolher a solução mais simples.

Não criar abstrações desnecessárias.

Não implementar funcionalidades para o futuro.

Construir apenas o necessário para a primeira versão.