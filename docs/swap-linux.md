# TUTORIAL DE CONFIGURAÇÃO DE SWAP E OTIMIZAÇÃO (AWS EC2)

Este guia documenta o procedimento de segurança aplicado na instância t4g.small para evitar travamentos de Kernel e MySQL.

---

## 1. CRIAÇÃO E ATIVAÇÃO DO SWAP (2GB)
O Swap permite que o sistema use o disco EBS como memória auxiliar quando a RAM de 2GB esgota.

# Passo a passo no terminal:
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile

---

## 2. CONFIGURAÇÃO DE PERSISTÊNCIA (BOOT)
Para que o Swap carregue automaticamente após um reboot:

1. Adicione a linha ao arquivo /etc/fstab:
   echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab

2. TESTE OBRIGATÓRIO (Valide a sintaxe antes de reiniciar):
   sudo mount -a
   # Se não houver erro no terminal, o arquivo fstab está correto.

---

## 3. AJUSTE DE SENSIBILIDADE (SWAPPINESS)
Configura o Linux para usar o Swap apenas em emergências.

# Define o valor para 10 (ideal para bancos de dados)
sudo sysctl vm.swappiness=10

# Grava a alteração para ser permanente
echo 'vm.swappiness=10' | sudo tee -a /etc/sysctl.conf

---

## 4. OTIMIZAÇÃO RECOMENDADA (MySQL)
Com 2GB de RAM + 2GB de Swap, a configuração sugerida é:

* Arquivo: /etc/mysql/my.cnf
* Parâmetro: innodb_buffer_pool_size = 1G
* Parâmetro: max_connections = 60

---

## 5. CHECKLIST DE SEGURANÇA AWS
* AMI (Backup): Criar imagem antes de reboots críticos.
* CPU Credit Mode: Definir como "Unlimited" no console AWS.

---

## 6. COMANDOS DE VERIFICAÇÃO
* Verificar Memória: free -h
* Verificar Swap Ativo: sudo swapon --show
* Verificar Uptime: uptime