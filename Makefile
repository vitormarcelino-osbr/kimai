include .env

# Variáveis de formatação para mensagem de sucesso e erro
.ONESHELL:
_SUCCESS := "\033[32m[%s]\033[0m %s\n" # Texto verde para "printf"
_ERROR := "\033[31m[%s]\033[0m %s\n"   # Texto vermelho para "printf"
dockerCompose = docker compose
dockerComposeFile = docker-compose.yml
COMPOSE_PROJECT_NAME = kimai

build:
	printf $(_SUCCESS) "🏗️ - BUILD DA IMAGEM" "!!" ;
	${dockerCompose} -f ${dockerComposeFile} build $(if $(command),$(command),'')
rebuild:
	printf $(_SUCCESS) "🏗️ - REBUILD DA IMAGEM" "!!" ;
	make -s build command='--no-cache' && make restart
up:
	printf $(_SUCCESS) "🆙 - SUBINDO OS CONTAINERS" "!!" ;
	${dockerCompose} -f ${dockerComposeFile} up -d
down:
	printf $(_SUCCESS) "🛑 - PARANDO E REMOVENDO CONTAINERS" "!!" ;
	${dockerCompose} -f ${dockerComposeFile} down
stop:
	printf $(_SUCCESS) "🛑 - PARANDO OS CONTAINERS" "!!" ;
	${dockerCompose} -f ${dockerComposeFile} stop
destroy:
	printf $(_SUCCESS) "🛑 - DESTRUINDO OS CONTAINERS" "!!" ;
	${dockerCompose} -f ${dockerComposeFile} down -v --remove-orphans
restart:
	printf $(_SUCCESS) "🛑 - REINICIANDO OS CONTAINERS" "!!" ;
	make -s down
	#make -s recreatevolume
	make -s up
recreatevolume:
	printf $(_SUCCESS) "🗑️ - Removendo volumes docker composer" "!!" ;
	for volume_name in "${COMPOSE_PROJECT_NAME}_data" "${COMPOSE_PROJECT_NAME}_mysql"; do \
		if docker volume inspect $$volume_name &> /dev/null; then \
			printf $(_SUCCESS) "✨ - Removendo o volume $$volume_name..." "!!" ; \
			docker volume rm $$volume_name; \
		fi; \
	done