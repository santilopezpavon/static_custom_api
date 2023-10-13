// We import the fs module
import fs from 'fs';
import path from 'path';

// We export the function that handles the requests to /api/files
export default async function handler(req, res) {
  // We get the method of the request
  const method = req.method;

  // We get the name of the file that we want to delete or create
  //const fileName = req.query.fileName;
  const { fileName } = req.body;

  // We check if the name of the file is valid
  if (!fileName) {
    // If it is not, we send a status code 400 and an error message
    res.status(400).send('The name of the file is missing');
    return;
  }

// We create a variable to store the path of the file

  const filePath = path.join(process.cwd(), 'custom-build' + fileName);



  // Depending on the method of the request, we execute different actions
  switch (method) {
    case 'DELETE':
      let stats = await fs.promises.stat(filePath);

      // Comprueba si es un directorio o un fichero
      if (stats.isDirectory()) {
        await fs.promises.rmdir(filePath, { recursive: true });
        res.status(200).send('Directory deleted');
      } else {
        // If the method is DELETE, we try to delete the file
        fs.unlink(filePath, (err) => {
          if (err) {
            // If there is an error, we send a status code 500 and an error message
            res.status(500).send(`Error deleting the file: ${err.message}`);
          } else {
            // If there is no error, we send a status code 200 and a success message
            res.status(200).send('File deleted');
          }
        });
      }

     
      break;
    case 'POST':
      // If the method is POST, we try to create or replace the file

      // We get the body of the request as a JSON string
      const body = req.body;

      // We convert the JSON string into a JavaScript object
      const data = body.data;

      // We convert the JavaScript object into a JSON string with format
      const json = JSON.stringify(data, null,0);
      createOrReplaceFile(filePath, json)
      // Usamos then para obtener el resultado de la función
      .then((result) => {
        // Mostramos el resultado por consola
        res.status(result.status).send(result.message);

      });

      break;
    default:
      // If the method is neither DELETE nor POST, we send a status code 405 and an error message
      res.status(405).send('Method not allowed');
  }
}


// Declaramos la función como asíncrona con la palabra clave async
async function createOrReplaceFile(filePath, data) {
  // Obtenemos la ruta del directorio a partir de la ruta del archivo
  const dirPath = path.dirname(filePath);

  try {
    // Usamos await para esperar a que se cree el directorio
    // Usamos fs.promises.mkdir() en lugar de fs.mkdir()
    await fs.promises.mkdir(dirPath, { recursive: true });
    // Usamos await para esperar a que se cree o reemplace el archivo
    // Usamos fs.promises.writeFile() en lugar de fs.writeFile()
    await fs.promises.writeFile(filePath, data, { flag: 'w' });
    // Devolvemos un objeto con el estado y el mensaje de éxito
    return {
      "status": 201,
      "message": "File created or replaced"
    };
  } catch (err) {
    // Si hay algún error, lo capturamos con catch y devolvemos un objeto con el estado y el mensaje de error
    return {
      "status": 500,
      "message": `Error creating or replacing the file: ${err.message}`
    };
  }
}
