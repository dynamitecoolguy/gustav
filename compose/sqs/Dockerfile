FROM openjdk:8-jre-alpine

ARG ELASTICMQ_VERSION
ENV ELASTICMQ_VERSION ${ELASTICMQ_VERSION:-0.15.3}

RUN apk add --no-cache curl ca-certificates
RUN mkdir -p /opt/elasticmq/log /opt/elasticmq/lib /opt/elasticmq/config
RUN curl -sfLo /opt/elasticmq/lib/elasticmq.jar https://s3-eu-west-1.amazonaws.com/softwaremill-public/elasticmq-server-${ELASTICMQ_VERSION}.jar
COPY custom.conf /opt/elasticmq/config/custom.conf

WORKDIR /opt/elasticmq

EXPOSE 9324

ENTRYPOINT [ "/usr/bin/java", "-Dconfig.file=/opt/elasticmq/config/custom.conf", "-jar", "/opt/elasticmq/lib/elasticmq.jar" ]
