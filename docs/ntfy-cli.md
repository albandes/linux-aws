Claro! Aqui está o tutorial em formato **Markdown (`.md`)** para você salvar ou compartilhar:

---

```markdown
# Como instalar o ntfy CLI em uma EC2 com Amazon Linux 2023

Um guia passo a passo para instalar o cliente **ntfy** (ntfy.sh) em uma instância **Amazon EC2** rodando **Amazon Linux 2023**.

---

## Pré-requisitos
- Uma instância EC2 com Amazon Linux 2023.
- Acesso SSH (chave PEM ou usuário `ec2-user`).
- Permissões de `sudo`.

---

## Passo a Passo

### 1. Conectar à instância EC2
```bash
ssh -i "sua-chave.pem" ec2-user@seu-ip-publico
```

### 2. Instalar dependências
Atualize os pacotes e instale `wget` e `tar`:
```bash
sudo dnf update -y
sudo dnf install -y wget tar gzip
```

### 3. Baixar o ntfy CLI
Obtenha a versão mais recente do [GitHub](https://github.com/binwiederhier/ntfy/releases).  
Substitua `amd64` por `arm64` se estiver em uma instância Graviton (ARM):

```bash
NTFY_VERSION=$(curl -s https://api.github.com/repos/binwiederhier/ntfy/releases/latest | grep 'tag_name' | cut -d '"' -f 4)
wget "https://github.com/binwiederhier/ntfy/releases/download/${NTFY_VERSION}/ntfy_${NTFY_VERSION}_linux_amd64.tar.gz"
```

### 4. Extrair e instalar
```bash
tar -xzf ntfy_${NTFY_VERSION}_linux_amd64.tar.gz
sudo mv ntfy_${NTFY_VERSION}_linux_amd64/ntfy /usr/local/bin/
```

Verifique a instalação:
```bash
ntfy --version
```

### 5. Testar o ntfy
Envie uma notificação para um tópico público:
```bash
ntfy publish seu-topico-aleatorio "Olá, Amazon Linux 2023!"
```
Acesse [ntfy.sh/seu-topico-aleatorio](https://ntfy.sh/seu-topico-aleatorio) para ver a mensagem.

---

## Configuração Avançada (Opcional)
Para rodar o ntfy como **servidor** local:
1. Crie um arquivo de configuração em `/etc/ntfy.yml` (baseado no [modelo oficial](https://ntfy.sh/docs/config/)).
2. Inicie o serviço com `systemd`:
   ```bash
   sudo ntfy serve
   ```

---

## Referências
- [Site oficial ntfy](https://ntfy.sh)
- [Documentação de instalação](https://ntfy.sh/docs/install/)
- [Repositório GitHub](https://github.com/binwiederhier/ntfy)

---

> **Nota**: Para tópicos privados, configure autenticação no arquivo `ntfy.yml`.  
> 📌 Mantenha seu tópico **secreto** se enviar dados sensíveis.
```

---

### Como usar este arquivo?
1. Salve como `instalar-ntfy-ec2.md`.
2. Use em plataformas como GitHub, GitLab ou converta para HTML/PDF com ferramentas como `pandoc`.

Se precisar de ajustes, é só pedir! 😊