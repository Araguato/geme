# Guía: Persistencia de imágenes en aplicaciones Laravel desplegadas en CapRover

Esta guía documenta los pasos para garantizar que los archivos almacenados en `storage/app/public` permanezcan disponibles tras cada despliegue o reinicio del contenedor. Úsala en todas las aplicaciones Laravel hospedadas en CapRover (por ejemplo, **wawi-demo**, **fuorum** y **restaurante**).

## 1. Requisitos

- El código debe incluir la recreación automática del symlink `public/storage` en el arranque. En este proyecto se implementó en `App\Providers\AppServiceProvider::boot()`.
- Debes tener acceso al panel de CapRover y a la terminal del servidor para ejecutar comandos `docker exec` cuando sea necesario.

## 2. Configurar el volumen persistente en CapRover

1. En el panel de CapRover abre la aplicación correspondiente.
2. Ve a **Configuraciones de la App → Mounted Persistent Directories** (o edita la sección *Service Update Override* si usas JSON).
3. Asegúrate de que el volumen de almacenamiento apunte exactamente a la ruta del proyecto Laravel dentro del contenedor:
   ```json
   {
     "Type": "volume",
     "Source": "captain--NOMBRE-DEL-VOLUMEN",
     "Target": "/var/www/html/storage/app/public"
   }
   ```
   > Nota: muchas imágenes base de Laravel en CapRover colocan el proyecto en `/var/www/html`. Si usas otra imagen personalizada, verifica la ruta real antes de aplicar la configuración.
4. Guarda los cambios y despliega/reinicia la aplicación desde CapRover.

## 3. Verificar el montaje y el symlink

Después del despliegue:

1. Entra al contenedor:
   ```bash
   docker exec -it <nombre-del-servicio> sh
   cd /var/www/html
   ```
2. Comprueba que el volumen esté montado en la ruta correcta:
   ```bash
   mount | grep storage
   ```
   Debe devolver una línea con `on /var/www/html/storage/app/public`.
3. Revisa el symlink:
   ```bash
   ls -l public | grep storage
   ```
   La salida esperada es `storage -> /var/www/html/storage/app/public`.
4. Lista los archivos para confirmar que se ven desde el contenedor:
   ```bash
   find storage/app/public -maxdepth 2 -type f
   ```
   Deberías ver imágenes existentes (`products/`, `categories/`, etc.).

## 4. Ajustar permisos (solo si se requiere)

Si aparece un error de permisos al escribir logs o subir archivos, ejecuta:
```bash
docker exec -it <nombre-del-servicio> sh -c "cd /var/www/html && chown -R www-data:www-data storage bootstrap/cache && chmod -R ug+rw storage bootstrap/cache"
```
Esto restablece la propiedad y permisos para que PHP (usuario `www-data`) pueda escribir en `storage` y `bootstrap/cache`.

## 5. Pruebas de regresión

1. Sube una imagen desde la aplicación (producto, categoría, etc.).
2. Forza un reinicio desde CapRover (**Guardar y Reiniciar**).
3. Recarga la página con `Ctrl+F5` y verifica que la imagen persista.
4. Opcional: vuelve a listar los archivos dentro del contenedor para confirmar que siguen presentes.

## 6. Resolución de problemas comunes

| Síntoma | Causa probable | Acción |
|---------|----------------|--------|
| Las imágenes desaparecen tras cada reinicio | El volumen está montado en una ruta distinta a `/var/www/html/storage/app/public` | Corrige el `Target` del volumen y despliega de nuevo. |
| Error `The stream or file "storage/logs/laravel.log" could not be opened: Permission denied` | El directorio `storage` tiene permisos de `root` tras ejecutar comandos manuales | Ejecuta el comando de **Ajustar permisos** mencionado arriba. |
| El comando `storage:link` falla con `Target [...] already exists and is not a symlink` | Existe un directorio real en `public/storage` | Borra la carpeta (`rm -rf public/storage`) y vuelve a ejecutar `php artisan storage:link`. |
| No se recrea el symlink automáticamente | El código no incluye la comprobación en `AppServiceProvider` o se ejecuta en un entorno distinto | Verifica la lógica en `App\Providers\AppServiceProvider` y ajusta según la ruta real del proyecto. |

---

Sigue esta guía para cualquier nueva aplicación Laravel en CapRover y evitarás la pérdida de imágenes tras despliegues o reinicios.
