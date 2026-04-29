/**
 * Testimonial block — entry point.
 *
 * Imports block metadata from block.json and registers the block type with
 * the edit and save implementations.
 */
import { registerBlockType } from '@wordpress/blocks';

import metadata from '../block.json';
import Edit from './edit';
import save from './save';
import './style.scss';

registerBlockType( metadata.name, {
    ...metadata,
    edit: Edit,
    save,
} );
