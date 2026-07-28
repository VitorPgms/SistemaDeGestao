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
- Livewire como motor de regra de negócio nas telas principais (forms e tabelas continuam Blade + Controller; navegação via Livewire é permitida — ver seção Navegação)
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

Filament é usado para Cadastros (Resources completos) e como casca de navegação (Pages finas, sem Forms/Tables do Filament) para Operações e BI.

Blade continua sendo o conteúdo de:

- Dashboard
- Entradas
- Saídas
- Estoque
- Relatórios

Regra de negócio, validação e persistência dessas telas continuam em Controllers, Requests e Services — a Page do Filament só entrega a mesma view (GET); toda escrita (POST/PUT) continua batendo nos Controllers de sempre. Ver seção Navegação.

---

# Navegação

Todo o sistema (Cadastros, Operações e BI) compartilha o mesmo shell do Filament: mesmo menu lateral, mesmo cabeçalho, mesma navegação. O usuário nunca deve sentir que saiu do sistema ao alternar entre eles.

A exibição (GET) de cada tela de Operações/BI é uma Filament Page fina, que devolve a mesma view Blade que já existia, sem usar Forms/Tables do Filament. Isso reaproveita o shell nativo do Filament em vez de replicar manualmente um layout parecido.

Toda escrita continua fora do Livewire: os formulários das telas de Operações submetem para os mesmos Controllers, Requests e Services de sempre (POST/PUT comuns). A Page do Filament só troca o "envelope" da tela; cadastro, edição, exclusão e regra de negócio continuam exatamente como estão.

Cadastros continua em Filament Resources, como sempre. A diferença é que Operações e BI passam a usar Filament Pages para a exibição, em vez de Controllers retornando view diretamente — sem mudar Models, Services, Policies ou banco de dados.

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