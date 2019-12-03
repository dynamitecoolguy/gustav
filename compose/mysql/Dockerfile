FROM mariadb:10.4.10

ENV MYSQL_ROOT_PASSWORD gustav
ENV MYSQL_ROOT_HOST 172.28.0.1

ENV MYSQL_DATABASE userdb
ENV MYSQL_USER scott
ENV MYSQL_PASSWORD tiger

COPY init.d/* /docker-entrypoint-initdb.d/

COPY mysqld.cnf /etc/mysql/mariadb.conf.d/
COPY client.cnf /etc/mysql/mariadb.conf.d/

EXPOSE 3306
