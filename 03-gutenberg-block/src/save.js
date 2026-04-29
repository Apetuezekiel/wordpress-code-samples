/**
 * Testimonial block — save component.
 *
 * The output of this function is serialised into post_content and must remain
 * stable across block versions. Any change to the markup requires either a
 * new `deprecated` entry or a block migration.
 */
import { useBlockProps, RichText } from '@wordpress/block-editor';

/**
 * Render the static HTML saved to the database.
 *
 * @param {Object} props            Block props.
 * @param {Object} props.attributes Block attributes.
 * @return {JSX.Element}
 */
export default function save( { attributes } ) {
    const {
        quote,
        author,
        role,
        backgroundColor,
        textColor,
        avatarUrl,
        rating,
    } = attributes;

    const blockProps = useBlockProps.save( {
        className: 'wp-block-ezekiel-testimonial',
        style: {
            backgroundColor,
            color: textColor,
        },
    } );

    return (
        <figure { ...blockProps }>
            { rating > 0 && (
                <div
                    className="testimonial__stars"
                    aria-label={ `${ rating } out of 5 stars` }
                >
                    { Array.from( { length: 5 }, ( _, i ) => (
                        <span
                            key={ i }
                            className={ `testimonial__star ${ i < rating ? 'is-filled' : '' }` }
                            aria-hidden="true"
                        >
                            { i < rating ? '★' : '☆' }
                        </span>
                    ) ) }
                </div>
            ) }

            <blockquote className="testimonial__body">
                <RichText.Content
                    tagName="p"
                    className="testimonial__quote"
                    value={ quote }
                />
            </blockquote>

            <figcaption className="testimonial__attribution">
                { avatarUrl && (
                    <img
                        src={ avatarUrl }
                        alt=""
                        className="testimonial__avatar"
                        aria-hidden="true"
                        loading="lazy"
                    />
                ) }
                <div className="testimonial__meta">
                    <RichText.Content
                        tagName="cite"
                        className="testimonial__author"
                        value={ author }
                    />
                    { role && (
                        <RichText.Content
                            tagName="span"
                            className="testimonial__role"
                            value={ role }
                        />
                    ) }
                </div>
            </figcaption>
        </figure>
    );
}
