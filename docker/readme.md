# Lanzar

## Comando para crear la imagen
> docker build --tag dpaypal:1.0 .
## Comando para lanzar el docker
Para poder subir las credenciales al servicio es necesario enlazar los directorios
> sudo docker run -d -p 8021:8021 --name paypal -v /home/ec2-user/dockers/paypal-docker/user:/paypal-docker/user -v /home/ec2-user/dockers/paypal-docker/log:/paypal-docker/log dpaypal:1.0