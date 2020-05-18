# Lanzar

## Comando para crear la imagen
> docker build --tag paypal:1.0
## Comando para lanzar el docker
Para poder subir las credenciales al servicio es necesario enlazar los directorios
> sudo docker run -d -p 8021:8021 --name paypal paypal:1.0