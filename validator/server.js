const express = require('express');
const bodyParser = require('body-parser');
const axios = require('axios');
const cheerio = require('cheerio');
const SHACLValidator = require('rdf-validate-shacl');
const N3 = require('n3');
const jsonld = require('jsonld');
const fs = require('fs');
const path = require('path');

const app = express();
const PORT = 3344;

app.use(bodyParser.json());

// Load Shapes
const shapesPath = path.join(__dirname, 'shapes.ttl');
const shapesContent = fs.readFileSync(shapesPath, 'utf8');

const parser = new N3.Parser();
const shapesDataset = new N3.Store();

// Initialize Validator
let validator;

parser.parse(shapesContent, (error, quad, prefixes) => {
    if (error) {
        console.error("Error parsing SHACL shapes:", error);
        process.exit(1);
    }
    if (quad) {
        shapesDataset.add(quad);
    } else {
        // Parsing complete
        validator = new SHACLValidator(shapesDataset, { factory: N3.DataFactory });
        console.log("SHACL Validator initialized with custom shapes.");
    }
});

app.post('/validate', async (req, res) => {
    const { url } = req.body;

    if (!url) {
        return res.status(400).json({ status: 'FAILED', reason: 'Missing "url"' });
    }

    console.log(`Validating URL: ${url}`);

    try {
        // 1. Fetch URL (insecure)
        const response = await axios.get(url, {
            httpsAgent: new (require('https').Agent)({ rejectUnauthorized: false }),
            headers: { 'User-Agent': 'Mozilla/5.0 (compatible; LocalValidator/1.0)' }
        });

        // 2. Extract JSON-LD
        const $ = cheerio.load(response.data);
        const scripts = $('script[type="application/ld+json"]');
        const jsonLdBlocks = [];

        scripts.each((i, el) => {
            try {
                const content = $(el).html();
                if (content) {
                    const parsed = JSON.parse(content);
                    jsonLdBlocks.push(parsed);
                }
            } catch (e) {
                console.error('Error parsing JSON-LD block', e);
            }
        });

        if (jsonLdBlocks.length === 0) {
            return res.json({ status: 'FAILED', reason: 'No JSON-LD found', url });
        }

        // 3. Convert JSON-LD to RDF (N-Quads) and Validate
        const report = {
            valid: 0,
            errors: []
        };

        for (const block of jsonLdBlocks) {
            try {
                // Expand JSON-LD to ensure @type is resolved to full IRIs (http://schema.org/Article)
                // We mock the context to schema.org if it's simple string,
                // but jsonld.toRDF handles standard contexts well.

                const nquads = await jsonld.toRDF(block, { format: 'application/n-quads' });

                // Parse N-Quads into a Store
                const dataStore = new N3.Store();
                const dataParser = new N3.Parser();

                await new Promise((resolve, reject) => {
                    dataParser.parse(nquads, (err, quad, prefixes) => {
                        if (err) reject(err);
                        if (quad) dataStore.add(quad);
                        else resolve();
                    });
                });

                // Validate
                const validationReport = validator.validate(dataStore);

                if (validationReport.conforms) {
                    report.valid++;
                } else {
                    // Extract helpful error messages
                    validationReport.results.forEach(result => {
                         let msg = result.message ? result.message.map(m => m.value).join(' ') : 'Constraint Violation';
                         let path = result.path ? result.path.value : 'unknown path';
                         let focusNode = result.focusNode ? result.focusNode.value : 'unknown node';
                         report.errors.push(`Violation at ${focusNode} (Path: ${path}): ${msg}`);
                    });
                }

            } catch (e) {
                report.errors.push(`Error processing block: ${e.message}`);
            }
        }

        if (report.errors.length > 0) {
            return res.json({
                status: 'FAILED',
                reason: 'Validation errors found',
                errors: report.errors,
                data: jsonLdBlocks // Return data for debugging
            });
        }

        return res.json({
            status: 'OK',
            message: `Found ${report.valid} valid blocks that conform to strict SHACL shapes.`,
            data: jsonLdBlocks
        });

    } catch (err) {
        console.error(`Error processing ${url}:`, err.message);
        return res.status(500).json({ status: 'FAILED', reason: `Internal Error: ${err.message}` });
    }
});

app.listen(PORT, () => {
    console.log(`Validator service running on port ${PORT}`);
});
