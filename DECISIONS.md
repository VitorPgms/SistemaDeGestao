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

## Segurança

Usuário comum enxerga apenas seu CD.

Administrador pode visualizar todos os CDs.

---

## Colaboradores

Status e data de demissão são consistentes entre si: colaborador inativo exige data de demissão; colaborador ativo não pode possuir data de demissão. Validado no formulário Filament (`requiredIf`/`prohibitedUnless`) e reforçado no Model via `saving` (defesa em profundidade, mesmo padrão já usado em `BelongsToCd`).

Setor vinculado a um colaborador deve pertencer ao mesmo CD do colaborador. Validado no formulário Filament (`exists` com `cd_id`) e reforçado no Model.

Um Setor não pode ser desativado enquanto possuir colaboradores ativos vinculados.

---

## Filosofia

Sempre escolher a solução mais simples.

Não criar abstrações desnecessárias.

Não implementar funcionalidades para o futuro.

Construir apenas o necessário para a primeira versão.