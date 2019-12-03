FROM postgres:10.11

ENV POSTGRES_DB logdb
ENV POSTGRES_USER scott
ENV POSTGRES_PASSWORD tiger

COPY init.d/* /docker-entrypoint-initdb.d/

EXPOSE 5432
