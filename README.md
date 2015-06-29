# GeneratorBundle
Define algunos comandos que me resultan de utilidad en los proyectos Symfony

## Funcionalidades

### fer:installdeps

Instala una serie de packages y dependecias que suelo emplear:

- JMS\DiExtraBundle, para injectar dependecias en el controlador sin definirlos como servicio
- Doctrine\FixturesBundle
- SimpleBusBundle  (SimpleBusCommandBusBundle, SimpleBusEventBusBundle, DoctrineOrmBridgeBundle), configurado para usar namedMessages.
define un fichero bus_config.yml en app/config/
- TbbcRestUtilBundle para gestionar excepciones y mostrar una Response, crea un fichero exceptions.yml en app/config/

Este comando también habilita el serializer de Symfony en el config.yml

### fer:command:generate

Genera una serie de clases necesarias para gestionar comandos:

- Crea el EjemploCommandHandler
- Crea el EjemploCommand
- Crea el servicio en bus_config.yml para asociar el Handler y el Command
