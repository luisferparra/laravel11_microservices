# create-topics.sh
#!/bin/bash

# Esperar hasta que el servicio Kafka esté disponible
KAFKA_HOST=kafka
KAFKA_PORT=9092
while ! nc -z $KAFKA_HOST $KAFKA_PORT; do   
  sleep 0.1 # Espera 0.1 segundos antes de volver a comprobar
done

# Crear topics
kafka-topics --create --topic AUTH_CREATE_ACCESS --bootstrap-server kafka:9092 --partitions 3 --replication-factor 1
kafka-topics --create --topic AUTH_CREATE_ACCESS_RESULT --bootstrap-server kafka:9092 --partitions 3 --replication-factor 1
kafka-topics --create --topic AUTH_DEFAULT_ERROR --bootstrap-server kafka:9092 --partitions 3 --replication-factor 1
