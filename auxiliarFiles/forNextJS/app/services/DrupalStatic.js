import path from 'path';
import { promises as fs } from 'fs';

module.exports = {
    // Array containing possible entity types
    possibleEntities: ["node", "taxonomy_term", "media", "paragraph"],

    /**
     * Get entity properties by alias.
     * @param {string} alias - The alias to retrieve data for.
     * @returns {object|false} - Entity data if found, or false if not found.
     */
    async getDataEntityPropsByAlias(alias) {
        // Check if the alias matches certain conditions
        const isOriginRoute = this.isOriginRoute(alias);
        let data = {};
        if (isOriginRoute !== false) {
            const fileName = this.getAbsolutePathEntityJson(
                isOriginRoute.entity_type,
                isOriginRoute.id_entity,
                isOriginRoute.lang
            );
            data = await this.getFileStatic(fileName);
            if (data !== false) {
                return {
                    "entity_type": isOriginRoute.entity_type,
                    "id_entity": isOriginRoute.id_entity,
                    "lang": isOriginRoute.lang
                };
            }
            return false;
        }

        // If the alias doesn't match the conditions, look elsewhere
        const jsonDirectory = path.join(process.cwd(), 'custom-build/alias');
        const pathAlias = jsonDirectory + alias + "/data.json";

        data = await this.getFileStatic(pathAlias);
        if (data !== false) {
            return await JSON.parse(data);
        }
        return false;
    },

    /**
     * Check if the alias matches specific conditions.
     * @param {string} alias - The alias to check.
     * @returns {object|false} - An object with alias information if it matches conditions, or false if it doesn't.
     */
    isOriginRoute(alias) {
        const arrayAlias = alias.split("/");

        if (arrayAlias.length < 3) {
            return false;
        }

        if (!this.possibleEntities.includes(arrayAlias[2])) {
            return false;
        }
        return {"entity_type": arrayAlias[2], "id_entity": arrayAlias[3], "lang": arrayAlias[1]};
    },

    /**
     * Get file data asynchronously.
     * @param {string} pathFile - The path to the file to read.
     * @returns {string|false} - File data if found, or false if not found.
     */
    async getFileStatic(pathFile) {
        try {
            const data = await fs.readFile(pathFile, 'utf8');
            return data;
        } catch (error) {
            return false;
        }
    },

    /**
     * Get the absolute path to the JSON entity file.
     * @param {string} target_type - The target entity type.
     * @param {string} target_id - The target entity ID.
     * @param {string} lang - The language.
     * @returns {string} - The absolute path to the entity JSON file.
     */
    getAbsolutePathEntityJson(target_type, target_id, lang) {
        const jsonDirectory = path.join(process.cwd(), 'custom-build');
        const subfolder = this.getSubFolderEntityJson(target_type, target_id);
        const nameFile = this.getFileName(target_type, target_id, lang);
        return `${jsonDirectory}/${subfolder}${nameFile}`;
    },

    // Function to calculate subfolder based on target_type and ID
    getSubFolderEntityJson(target_type, id) {
        if (!isNaN(id)) {
            let folder1 = Math.floor(id / 200);
            let folder2 = Math.floor(folder1 / 200);
            let folder3 = Math.floor(folder2 / 200);
            return `${target_type}/${folder1}/${folder2}/${folder3}/`;
        } else {
            return `${target_type}/`;
        }
    },

    /**
     * Get the file name based on entity properties.
     * @param {string} target_type - The target entity type.
     * @param {string} id - The target entity ID.
     * @param {string} lang - The language (default is 'neutral').
     * @returns {string} - The file name.
     */
    getFileName(target_type, id, lang = 'neutral') {
        return `${target_type}--${id}--${lang}.json`;
    },

    /**
     * Get recursive entities for a given target_type, target_id, and language.
     * @param {string} target_type - The target entity type.
     * @param {string} target_id - The target entity ID.
     * @param {string} lang - The language.
     * @returns {object} - The locationEntity object with recursively fetched entities.
     */
    async getRecursiveEntities(target_type, target_id, lang) {
        const locationEntity = await this.getEntityByProps(target_type, target_id, lang);
        for (const field_name in locationEntity) {
            const value_field = locationEntity[field_name];
            for (let index = 0; index < value_field.length; index++) {
                let value = value_field[index];
                if (value.hasOwnProperty("target_type") && value.hasOwnProperty("target_id") &&
                    this.possibleEntities.includes(value["target_type"])) {
                    value_field[index]["entity"] = await this.getEntityByProps(value["target_type"], value["target_id"], lang)
                }
            }
        }
        return locationEntity;
    },

    /**
     * Get entity data by entity properties.
     * @param {string} target_type - The target entity type.
     * @param {string} target_id - The target entity ID.
     * @param {string} lang - The language.
     * @returns {object} - Entity data if found, or an empty object if not found.
     */
    async getEntityByProps(target_type, target_id, lang) {
        const fileName = this.getAbsolutePathEntityJson(target_type, target_id, lang);
        const data = await this.getFileStatic(fileName);
        if (data === false) {
            return {};
        }
        return await JSON.parse(data);
    }
}
