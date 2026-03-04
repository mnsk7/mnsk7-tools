---
name: ssh_key_auth_playbook
description: Убирает ввод пароля по SSH: ключи, ssh-agent, GitHub secrets.
---

# SSH key auth

## Goal
Чтобы rsync/ssh работал без пароля.

## Steps (локально)
1) ssh-keygen -t ed25519 -C "deploy"
2) ssh-copy-id user@host  (или вручную в ~/.ssh/authorized_keys)
3) eval "$(ssh-agent -s)" && ssh-add ~/.ssh/id_ed25519
4) ~/.ssh/config:
   Host mnsk7-staging
     HostName <ip>
     User <user>
     IdentityFile ~/.ssh/id_ed25519
     IdentitiesOnly yes

## GitHub Actions
- приватный ключ в Secrets
- использовать ssh-agent action или писать ключ в файл и chmod 600
