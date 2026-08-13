import { type ReactElement, type ReactNode } from 'react';
import { Link, usePage } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';
import Pagination from '../../components/Pagination';
import DealListRow from '../../components/deals/DealListRow';
import DealMediaGallery from '../../components/deals/DealMediaGallery';
import FavoriteButton from '../../components/deals/FavoriteButton';
import { EmptyState } from '../../components/ui/EmptyState';
import type { Favorite, Paginated, SharedPageProps } from '../../types';

interface FavoritesIndexLinks {
    dealsIndex: string;
}

interface FavoritesIndexPageProps extends SharedPageProps {
    favorites: Paginated<Favorite>;
    links: FavoritesIndexLinks;
}

function favoriteMeta(favorite: Favorite): ReactNode {
    const { deal } = favorite;
    const searchLink = deal.huntedDealUrl ? (
        <Link href={deal.huntedDealUrl} prefetch="hover" className="text-beam hover:underline">
            {deal.searchTerm}
        </Link>
    ) : (
        <span className="text-dim">{deal.searchTerm}</span>
    );

    return (
        <>
            {deal.location && (
                <>
                    {deal.location} &middot;{' '}
                </>
            )}
            adăugat la favorite {favorite.createdAt} &middot; căutare: {searchLink}
        </>
    );
}

export default function FavoritesIndex(): ReactElement {
    const { favorites, links } = usePage<FavoritesIndexPageProps>().props;

    const header = (
        <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p className="placard mb-1.5 text-[0.6rem]">Selecție personală</p>
                <h2 className="font-sans text-xl font-bold leading-tight text-[#eaf4f6] sm:text-2xl">
                    Anunțuri favorite
                </h2>
                <p className="mt-1.5 font-mono text-[0.7rem] tabular-nums text-dim/70">
                    {favorites.meta.total} {favorites.meta.total === 1 ? 'anunț favorit' : 'anunțuri favorite'}
                </p>
            </div>
            <Link href={links.dealsIndex} className="beamkey focus-ring shrink-0 rounded-sm px-4 py-2.5 text-[0.65rem]">
                Toate anunțurile
            </Link>
        </div>
    );

    return (
        <AppLayout title="Anunțuri favorite" header={header}>
            <div className="py-8 sm:py-10">
                <div className="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                    <section aria-labelledby="favorites-ledger-heading">
                        <div className="mb-4">
                            <p className="placard text-[0.6rem]">Marcat cu inimioară</p>
                            <h3
                                id="favorites-ledger-heading"
                                className="mt-1 font-sans text-lg font-bold text-[#eaf4f6] sm:text-xl"
                            >
                                Lista favoritelor
                            </h3>
                        </div>

                        {favorites.data.length > 0 ? (
                            <>
                                <div className="border-t border-hairline">
                                    {favorites.data.map((favorite) => (
                                        <DealListRow
                                            key={favorite.id}
                                            deal={favorite.deal}
                                            variant="ledger"
                                            titleLimit={100}
                                            description={favorite.deal.description}
                                            meta={favoriteMeta(favorite)}
                                            leading={
                                                <DealMediaGallery deal={favorite.deal} mode="thumbnail" />
                                            }
                                            leadingActions={
                                                <FavoriteButton
                                                    toggleUrl={favorite.deal.toggleFavoriteUrl}
                                                    initialFavorited={favorite.deal.isFavorite}
                                                />
                                            }
                                        />
                                    ))}
                                </div>

                                {favorites.meta.lastPage > 1 && (
                                    <div className="mt-6 border-t border-hairline pt-4 font-mono text-sm text-dim">
                                        <Pagination
                                            meta={favorites.meta}
                                            links={favorites.links}
                                            entityLabel="Favorite"
                                        />
                                    </div>
                                )}
                            </>
                        ) : (
                            <EmptyState
                                title="Nicio favorită încă"
                                description="Apasă inimioara de lângă un anunț pentru a-l marca aici."
                                action={
                                    <Link
                                        href={links.dealsIndex}
                                        className="beamkey beamkey-armed focus-ring rounded-sm px-6 py-3 text-[0.7rem]"
                                    >
                                        Vezi anunțurile
                                    </Link>
                                }
                            />
                        )}
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
