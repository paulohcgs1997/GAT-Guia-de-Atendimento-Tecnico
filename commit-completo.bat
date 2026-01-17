@echo off
chcp 65001 >nul
cls
echo.
echo ╔════════════════════════════════════════════════════════════╗
echo ║     📝 GERADOR DE MENSAGEM DE COMMIT COMPLETA             ║
echo ╚════════════════════════════════════════════════════════════╝
echo.
echo    Gerando resumo de todas as funcionalidades...
echo.

REM Criar arquivo temporário com a mensagem
set TEMP_FILE=%TEMP%\commit_message.txt

(
echo Sistema completo de melhorias - Design ultra-compacto, busca avançada, GitHub API, versionamento automático
echo.
echo DESIGN: Redução 30-60%% espaçamentos, Bootstrap 5, modal compacto
echo BUSCA: Enter redirect, página Google-style, cards responsivos
echo GITHUB: Seleção branches, metadata .branch-info.json, API integration
echo VERSION: Sistema automático patch/minor/major, interface visual
echo ARQUIVOS: 15+ modificados/criados incluindo scripts batch interativos
) > "%TEMP_FILE%"

echo ════════════════════════════════════════════════════════════
echo.
echo    📋 MENSAGEM GERADA:
echo.
type "%TEMP_FILE%"
echo.
echo ════════════════════════════════════════════════════════════
echo.
set /p CONFIRM="    Deseja fazer o commit com esta mensagem? (S/N): "

if /i not "%CONFIRM%"=="S" (
    echo    ❌ Cancelado pelo usuário
    del "%TEMP_FILE%"
    timeout /t 2 >nul
    exit /b
)

echo.
echo    [1/4] 📋 Adicionando alterações...
git add .

echo    [2/4] 📦 Incrementando versão (MINOR)...
php increment_version.php minor

echo    [3/4] 💾 Criando commit...
git commit -F "%TEMP_FILE%"

echo    [4/4] ☁️  Enviando para o repositório...
git push

REM Ler nova versão
for /f "tokens=*" %%a in ('php -r "$v=json_decode(file_get_contents('version.json'),true); echo $v['version'];"') do set NEW_VERSION=%%a

echo.
echo ════════════════════════════════════════════════════════════
echo.
echo    ✅ COMMIT COMPLETO!
echo    📦 Nova Versão: %NEW_VERSION%
echo.
echo ════════════════════════════════════════════════════════════
echo.

del "%TEMP_FILE%"
pause
