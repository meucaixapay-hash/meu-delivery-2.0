# Meu Delivery 2.0

Repositório de desenvolvimento do Meu Delivery 2.0 para WordPress. A versão inicial preservada nesta árvore é **2.0.0-alpha.1**.

## Estrutura

- `plugin/`: código-fonte do plugin WordPress. Não contém o código do Meu Delivery 0.7.x.
- `artifacts/meu-delivery-2.0-fase1.zip`: ZIP original da Fase 1, preservado byte a byte.
- `scripts/package.ps1`: gera um ZIP instalável com a pasta raiz `meu-delivery-2.0/`.
- `dist/`: saída local do empacotamento; não é versionada.

## Empacotar para WordPress

No PowerShell, execute na raiz do repositório:

```powershell
./scripts/package.ps1
```

O arquivo gerado fica em `dist/meu-delivery-2.0-2.0.0-alpha.1.zip`. Para substituir conscientemente um ZIP gerado anteriormente, use `./scripts/package.ps1 -Force`.

O script valida a versão do cabeçalho e do código, e confirma que o ZIP contém somente um nível raiz `meu-delivery-2.0/`.

## Branches

- `main`: referência estável do repositório.
- `setup/phase1-version-control`: organização do código da Fase 1 e do processo de empacotamento.
- Próximas mudanças devem usar branches próprias, sem trabalhar diretamente em `main`.

O Meu Delivery 0.7.x é apenas referência histórica e não deve ser copiado para `plugin/`.
