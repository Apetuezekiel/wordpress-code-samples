/**
 * Testimonial block — editor component.
 */
import { __ } from '@wordpress/i18n';
import {
    useBlockProps,
    RichText,
    InspectorControls,
    MediaUpload,
    MediaUploadCheck,
} from '@wordpress/block-editor';
import {
    PanelBody,
    PanelRow,
    ColorPicker,
    RangeControl,
    Button,
    __experimentalHStack as HStack,
} from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Edit component for the Testimonial block.
 *
 * @param {Object}   props               Block props injected by the editor.
 * @param {Object}   props.attributes    Current block attributes.
 * @param {Function} props.setAttributes Attribute update function.
 * @return {JSX.Element}
 */
export default function Edit( { attributes, setAttributes } ) {
    const {
        quote,
        author,
        role,
        backgroundColor,
        textColor,
        avatarUrl,
        rating,
    } = attributes;

    const [ bgPickerOpen, setBgPickerOpen ] = useState( false );
    const [ textPickerOpen, setTextPickerOpen ] = useState( false );

    const blockProps = useBlockProps( {
        className: 'wp-block-ezekiel-testimonial',
        style: {
            backgroundColor,
            color: textColor,
        },
    } );

    /**
     * Render a row of star icons reflecting the current rating.
     *
     * @param {number} count Star count (1–5).
     * @return {JSX.Element}
     */
    const StarRating = ( { count } ) => (
        <div className="testimonial__stars" aria-label={ `${ count } out of 5 stars` }>
            { Array.from( { length: 5 }, ( _, i ) => (
                <span
                    key={ i }
                    className={ `testimonial__star ${ i < count ? 'is-filled' : '' }` }
                    aria-hidden="true"
                >
                    { i < count ? '★' : '☆' }
                </span>
            ) ) }
        </div>
    );

    return (
        <>
            { /* Sidebar controls */ }
            <InspectorControls>
                <PanelBody
                    title={ __( 'Colours', 'ezekiel-blocks' ) }
                    initialOpen={ true }
                >
                    <PanelRow>
                        <Button
                            variant="secondary"
                            onClick={ () => setBgPickerOpen( ! bgPickerOpen ) }
                        >
                            { __( 'Background colour', 'ezekiel-blocks' ) }
                        </Button>
                    </PanelRow>
                    { bgPickerOpen && (
                        <ColorPicker
                            color={ backgroundColor }
                            onChange={ ( value ) =>
                                setAttributes( { backgroundColor: value } )
                            }
                            enableAlpha
                            defaultValue="#f9f9f9"
                        />
                    ) }

                    <PanelRow>
                        <Button
                            variant="secondary"
                            onClick={ () => setTextPickerOpen( ! textPickerOpen ) }
                        >
                            { __( 'Text colour', 'ezekiel-blocks' ) }
                        </Button>
                    </PanelRow>
                    { textPickerOpen && (
                        <ColorPicker
                            color={ textColor }
                            onChange={ ( value ) =>
                                setAttributes( { textColor: value } )
                            }
                            defaultValue="#333333"
                        />
                    ) }
                </PanelBody>

                <PanelBody
                    title={ __( 'Rating', 'ezekiel-blocks' ) }
                    initialOpen={ false }
                >
                    <RangeControl
                        label={ __( 'Star rating', 'ezekiel-blocks' ) }
                        value={ rating }
                        onChange={ ( value ) => setAttributes( { rating: value } ) }
                        min={ 1 }
                        max={ 5 }
                        step={ 1 }
                    />
                </PanelBody>

                <PanelBody
                    title={ __( 'Avatar', 'ezekiel-blocks' ) }
                    initialOpen={ false }
                >
                    <MediaUploadCheck>
                        <MediaUpload
                            onSelect={ ( media ) =>
                                setAttributes( { avatarUrl: media.url } )
                            }
                            allowedTypes={ [ 'image' ] }
                            value={ avatarUrl }
                            render={ ( { open } ) => (
                                <HStack>
                                    { avatarUrl && (
                                        <img
                                            src={ avatarUrl }
                                            alt=""
                                            style={ {
                                                width: 48,
                                                height: 48,
                                                borderRadius: '50%',
                                                objectFit: 'cover',
                                            } }
                                        />
                                    ) }
                                    <Button variant="secondary" onClick={ open }>
                                        { avatarUrl
                                            ? __( 'Replace avatar', 'ezekiel-blocks' )
                                            : __( 'Upload avatar', 'ezekiel-blocks' ) }
                                    </Button>
                                    { avatarUrl && (
                                        <Button
                                            variant="link"
                                            isDestructive
                                            onClick={ () =>
                                                setAttributes( { avatarUrl: '' } )
                                            }
                                        >
                                            { __( 'Remove', 'ezekiel-blocks' ) }
                                        </Button>
                                    ) }
                                </HStack>
                            ) }
                        />
                    </MediaUploadCheck>
                </PanelBody>
            </InspectorControls>

            { /* Block canvas */ }
            <figure { ...blockProps }>
                <StarRating count={ rating } />

                <blockquote className="testimonial__body">
                    <RichText
                        tagName="p"
                        className="testimonial__quote"
                        value={ quote }
                        onChange={ ( value ) => setAttributes( { quote: value } ) }
                        placeholder={ __( 'Write the testimonial quote…', 'ezekiel-blocks' ) }
                        allowedFormats={ [ 'core/bold', 'core/italic' ] }
                    />
                </blockquote>

                <figcaption className="testimonial__attribution">
                    { avatarUrl && (
                        <img
                            src={ avatarUrl }
                            alt=""
                            className="testimonial__avatar"
                            aria-hidden="true"
                        />
                    ) }
                    <div className="testimonial__meta">
                        <RichText
                            tagName="cite"
                            className="testimonial__author"
                            value={ author }
                            onChange={ ( value ) => setAttributes( { author: value } ) }
                            placeholder={ __( 'Author name', 'ezekiel-blocks' ) }
                            allowedFormats={ [] }
                        />
                        <RichText
                            tagName="span"
                            className="testimonial__role"
                            value={ role }
                            onChange={ ( value ) => setAttributes( { role: value } ) }
                            placeholder={ __( 'Job title, Company', 'ezekiel-blocks' ) }
                            allowedFormats={ [] }
                        />
                    </div>
                </figcaption>
            </figure>
        </>
    );
}
