# ERP AGN

## Objetivo

Sistema de Gestão de Estoque Corporativo para controle de produtos, entradas, saídas, colaboradores, setores e centros de distribuição.

A prioridade é resolver as necessidades atuais da empresa com uma arquitetura simples, organizada e fácil de manter.

---

# Tecnologias

- Laravel 12
- PHP 8.4
- Filament 4
- Blade
- MySQL
- Alpine.js (apenas quando necessário)
- Chart.js para gráficos

Não utilizar:

- React
- Vue
- Livewire nas telas principais
- Microserviços
- Arquiteturas complexas

---

# Filosofia do Projeto

Sempre priorizar:

- Simplicidade
- Organização
- Fácil manutenção
- Performance
- Código limpo

Não implementar funcionalidades pensando em possíveis necessidades futuras.

Implementar apenas o que será utilizado na primeira versão do sistema.

---

# Arquitetura

Monólito Modular.

Controllers devem ser extremamente simples.

Toda regra de negócio deve ficar em Services apenas quando houver complexidade suficiente para justificar.

Regras simples podem permanecer no próprio Resource ou em Observers.

Policies controlam autorização.

Filament será utilizado apenas para cadastros administrativos.

Blade será utilizado para:

- Dashboard
- Entradas
- Saídas
- Estoque
- Relatórios

---

# Organização

Cadastros

- Produtos
- Categorias
- Fornecedores
- Colaboradores
- Setores
- Usuários
- Centros de Distribuição

Operações

- Entradas
- Saídas
- Estoque

BI

- Dashboard
- Relatórios

Configurações

- Motivos de Saída
- Permissões
- Parâmetros

---

# Banco de Dados

Categorias são globais.

Produtos são globais.

Fornecedores pertencem ao CD.

Estoque pertence ao CD.

Entradas pertencem ao CD.

Saídas pertencem ao CD.

Colaboradores pertencem ao CD.

Setores pertencem ao CD.

Usuários pertencem ao CD.

Administrador pode visualizar todos os CDs.

Usuários comuns apenas seu CD.

---

# Dashboard

Dashboard deve conter indicadores visuais.

Utilizar Chart.js.

Sempre possuir filtros.

Os gráficos devem ser claros e objetivos.

---

# Padrões

Nunca duplicar código.

Sempre reutilizar componentes.

Sempre utilizar Soft Deletes quando fizer sentido.

Nunca excluir histórico operacional.

Sempre utilizar Foreign Keys.

Sempre utilizar Transactions quando alterar estoque.

Sempre documentar decisões importantes.