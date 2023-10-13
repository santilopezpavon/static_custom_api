// Import the DrupalStatic service from the app folder
import DrupalStatic from './app/service/DrupalStatic';
 
// Define the default handler function for the api route
// This function takes a request and a response as parameters
// The request contains the alias of an entity in the body
// The response is used to send back the data of the entity and its nested entities
export default async function handler(req, res) {
  // Destructure the alias property from the request body
  const { alias } = req.body;
  // Call the getDataEntityPropsByAlias method from the DrupalStatic service
  // to get the data of the entity by its alias
  // The data is an object with the entity_type, id_entity and lang properties
  // or false if the entity was not found
  const dataBaseEntity = await DrupalStatic.getDataEntityPropsByAlias(alias);
  // If the data is false, it means the entity was not found
  // So send a null response with a 404 status code
  if(dataBaseEntity === false) {
    res.json(null, 404);
  }

  // Otherwise, destructure the entity_type, id_entity and lang properties from the data
  const { entity_type, id_entity, lang } = dataBaseEntity;
  // Call the getRecursiveEntities method from the DrupalStatic service
  // to get the nested entities of the entity by its type, id and language
  // The nested entities are added as a new property called entity to each value of each field of the entity
  // The result is an object with all the fields and values of the entity and its nested entities
  const folderEntity = await DrupalStatic.getRecursiveEntities(entity_type, id_entity, lang);
  // Send the folderEntity as a JSON response with a 200 status code
  res.json(folderEntity, 200);
 
}
