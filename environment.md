# Gustav 
 
## 環境構築
 
Gustavの開発を行うためには、以下のソフトウェアが必要です。
 
 
### PHP
 
* PHP(7.3以上)
* composer
 
Ubuntuでのインストール手順例です。
 
```shell script
$ sudo apt-add-repository ppa:ondrej/php
$ sudo apt update
$ sudo apt install php7.3-cli
$ sudo apt install php7.3-mbstring php7.3-zip php7.3-json php7.3-bcmath php7.3-curl php7.3-xml php7.3-mysql php7.3-pgsql php7.3-gd
$ sudo apt install php-apcu php-apcu-bc php-igbinary php-msgpack php-redis php-xdebug php-yaml
$ sudo apt install composer
```

### Docker

* Docker
* docker-compose

Ubuntuでのインストール手順例です。

```shell script
$ sudo addgroup --system docker
$ sudo adduser $(whoami) docker
$ newgrp docker
$ sudo snap install docker
$ sudo systemctl restart snap.docker.dockerd
```

### git

* git
* git-crypt

Ubuntuでのインストール手順例です。

```shell script
$ sudo apt install git git-crypt gnupg
$ ssh-keygen -t rsa
$ eval "$(ssh-agent -s)"
$ ssh-add ~/.ssh/id_rsa
$ cat ~/.ssh/id_rsa.pub   (Copy to github)
$ git config --global user.email "<<YOUR MAIL ADDRESS>>"
$ git config --global user.name "<<YOUR NAME>>"
```

### FlatBuffers

クライアントとのデータ交換用に使用する場合はインストールします。
Ubuntuでのインストール手順例です。

```shell script
$ sudo snap install cmake --classic
$ wget https://github.com/google/flatbuffers/archive/1.11.0.tar.gz
$ tar xvzf 1.11.0.tar.gz
$ cd flatbuffers-1.11.0
$ cmake -G "Unix Makefiles" -DCMAKE_BUILD_TYPE=Release
$ make
$ make test
$ sudo make install
```

### AWS CLI

開発には必要としませんが、動作確認時などにあると便利です。
Ubuntuでのインストール手順例です。

```shell script
$ sudo apt install awscli
$ aws configure (必要なら--profile xxxxxxを付ける)
  AWS Access Key ID [None]: A*******************
  AWS Secret Access Key [None]: ****************************************
  Default region name [None]: ap-northeast-1
  Default output format [None]: json
```

