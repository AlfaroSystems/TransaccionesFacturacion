# Reglas de Workspace

- **Subir cambios a GitHub (git push):** Los cambios se pueden subir al repositorio remoto en GitHub según solicitud del usuario.
- **Mensajes de Commit en Git:** Todos los mensajes de commit (`git commit`) deben escribirse siempre en español.
- **Base de Datos y Migraciones:** NUNCA ejecutar `php artisan migrate:fresh` ni `migrate:refresh` sin la autorización explícita del usuario, ya que elimina los datos existentes. Toda modificación a las estructuras de tablas debe realizarse mediante nuevas migraciones incrementales (`php artisan migrate`) para preservar los datos registrados por el usuario.