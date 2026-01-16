# Diretório de Backups

Este diretório armazena backups automáticos do sistema criados antes de aplicar atualizações.

## Backups Automáticos

Quando você aplica uma atualização via GitHub, o sistema automaticamente:
1. Cria um backup completo do sistema atual
2. Salva neste diretório com timestamp
3. Aplica a atualização
4. Em caso de erro, pode restaurar o backup

## Formato dos Arquivos

- Nome: `backup_YYYY-MM-DD_HH-MM-SS.zip`
- Conteúdo: Todos os arquivos exceto uploads e backups anteriores

## Restauração Manual

Para restaurar um backup:
1. Extraia o arquivo .zip desejado
2. Substitua os arquivos do sistema pelos do backup
3. Mantenha o arquivo `src/config/conexao.php` (configurações do banco)

**⚠️ Importante:** Não delete este diretório. Os backups são essenciais para segurança do sistema.
